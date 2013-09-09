<?php
/*
Plugin Name: HeritageAction Score Card Member Score
Plugin URI: http://www.heritageaction.com
Description: HeritageAction Score Card Members of Congress
Version: 1.0
Author: Heritage Action for America
Author URI: http://heritageaction.com

*/

// bump
define("WPCURL", HAScoreMembers::getWpContentUrl());
define("WPURL", HAScoreMembers::getWpUrl());
define("HASCORE_MEMBER_URL", WPCURL . '/plugins/heritageaction-score-card-member-scores');


add_action('init',  'hascore_init');
add_action("admin_menu", "hascore_menus");


function hascore_init(){
  
  add_shortcode('mc_name', array('HAScoreMembers','mc_name'));
  add_action('media_buttons', array('HAScoreMembers', 'add_form_button'), 21);
  
  add_action('wp_head', array('HAScoreMembers','hascore_headstyle'));
  add_action('admin_head', array('HAScoreMembers','enqueueAdminScripts'));
  if(in_array(basename($_SERVER['PHP_SELF']), array('post.php', 'page.php', 'page-new.php', 'post-new.php'))){
      add_action('admin_footer',  array('HAScoreMembers', 'add_mc_name_popup'));
  }
  
}

if(is_admin()){
  $hascore_settings_page = new HAScoreSettingsPage();
}

class  HAScoreSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'HeritageAction Score Card Members', 
            'Score Card', 
            'manage_options', 
            'scorecard-setting-admin', 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'hascore_member_options' );
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>HeritageAction Score Card</h2>
            
            <?php            
            if(!empty($this->options['scorecard_api_key'])) :
              
              if(@ !file_get_contents('http://heritageactionscorecard.com/api/scorecard/members/congress/113/format/json/apikey/'. $this->options['scorecard_api_key'] .'/')):              
            ?>
              <div id="setting-error-settings_updated" class="settings-error error"> 
                <p><strong>Invalid API Key</strong></p></div>
              </div>     
            <?php 
                $this->options['scorecard_api_key'] = '';
                update_option('hascore_member_options', $this->options);
                
              endif; endif; ?>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'hascore_option_group' );   
                do_settings_sections( 'scorecard-setting-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
        HAScoreMembers::updateMemberScores();
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'hascore_option_group', // Option group
            'hascore_member_options', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'Score Card Member Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'scorecard-setting-admin' // Page
        );  

        add_settings_field(
            'scorecard_api_key', // ID
            'Score Card API Key', // Title 
            array( $this, 'scorecard_api_key_callback' ), // Callback
            'scorecard-setting-admin', // Page
            'setting_section_id' // Section           
        );      

        add_settings_field(
            'enable_popup', 
            'Enable Popup', 
            array( $this, 'enable_popup_callback' ), 
            'scorecard-setting-admin', 
            'setting_section_id'
        );      
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        if( !empty( $input['scorecard_api_key'] ) )
            $input['scorecard_api_key'] = sanitize_text_field( $input['scorecard_api_key'] );

        //if( !empty( $input['enable_popup'] ) )
        //    $input['enable_popup'] = sanitize_text_field( $input['enable_popup'] );

        return $input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your Scorecard configuration below. Don\'t have an API Key? <a href="http://heritageaction.com/request-score-card-api-key/" target="_blank">Get one here.</a>';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function scorecard_api_key_callback()
    {
        printf(
            '<input type="text" id="scorecard_api_key" name="hascore_member_options[scorecard_api_key]" value="%s" />',
            esc_attr( $this->options['scorecard_api_key'])
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function enable_popup_callback()
    {
        echo (
            '<label for="enable_popup"><input type="checkbox" id="enable_popup" name="hascore_member_options[enable_popup]" value="1" ' . checked(1, $this->options['enable_popup'], false) . ' /> Enable Popup boxes for Members of Congress</label>'
        );
    }
}



class HAScoreMembers{
  
  
  public static function enqueueAdminScripts() {
    wp_enqueue_script('autocomplete', HASCORE_MEMBER_URL . '/js/jquery.autocomplete.js', array('jquery'));
  }
  
  public function hascore_headstyle(){
    $hascore_member_options = get_option( 'hascore_member_options' );
    ob_start();
    ?>
    
    <style type="text/css" media="screen">
      .mc-bubble-wrap{
		    display:inline-block;		    
		  }
		  .mc-bubble-wrap{
		    font-weight:bold;
		    text-decoration:underline;
		    position:relative;
		  }
		  .mc-bubble-score{
		    font-size:0.8em;
		    padding:2px 4px;
		    margin-left:0.5em;
		    background:#143359;
		    color:#fff;
		    -webkit-border-radius: 10px;
        border-radius: 10px;
        position:relative;
        display:inline-block;
		  }
		  
		  .mc-scorecard-bubble-wrapper{
		    display:none;
		    margin-top:2.2em;
		    border:5px solid #4D4D4D;
		    position:absolute;
		    text-align:center;
		    width:305px;
		    background:#fff;
		    min-height:275px;
		    z-index:3000;
		    left:-15px !important;
		  }
		  .score-bubble-triangle{
		    position:absolute;
		    top:-15px;
		    left:10px;
		    width: 0; 
        height: 0; 
      	border-left: 15px solid transparent;  /* left arrow slant */
      	border-right: 15px solid transparent; /* right arrow slant */
      	border-bottom: 15px solid #4D4D4D; /* bottom, add background color here */
      	font-size: 0;
      	line-height: 0; 
		  }
		  .score-bubble-mini-triangle{
		    position:absolute;
		    top:8px;
		    left:-7px;
		    width: 0; 
        height: 0; 
      	border-left: 7px solid transparent;  /* left arrow slant */
      	border-right: 7px solid transparent; /* right arrow slant */
      	border-bottom: 7px solid #3AB0EE; /* bottom, add background color here */
      	font-size: 0;
      	line-height: 0;
		  }
		  .score-bubble-headline{
		    text-align:center;
		    text-transform:uppercase;
		    display:inline-block;
        color: white;
        font-family: HelveticaNeue-CondensedBold, 'Open Sans Condensed', Arial, sans-serif;
        font-size:2em;
        line-height:1.2em;

        padding:0 0 5px 0;

        
        background-color: #43b2ec;
        background-image: -ms-linear-gradient(top, #43b2ec, #1d61d7);
        background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#43b2ec), to(#1d61d7));
        background-image: -webkit-linear-gradient(top, #43b2ec,#1d61d7);
        background-image: -o-linear-gradient(top, #43b2ec, #1d61d7);
        background-image: -moz-linear-gradient(top, #43b2ec,#1d61d7);
        background-image: linear-gradient(top, #43b2ec,#1d61d7);
        background-repeat: repeat-x;
        filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#43b2ec', endColorstr='#1d61d7', GradientType=0);
      
		  }
		  .score-bubble-headline .bubble-title{
		    font-size:1.4em;
		    display:inline-block;
		  }
		  
		  .score-bubble-image{
		    float:left;
		    width:105px;
		  }
		  .score-bubble-info{
		    position:relative;
		    float:left;
		    display:inline-block;
		    width:180px;
		    text-align:left;
	    }
		  
		  .score-bubble-member-name{
		    margin:5px 10px;
		    font-family: HelveticaNeue-CondensedBold, 'Open Sans Condensed', Arial, sans-serif;
        font-weight: bold;
        color:#4D4D4D;
        font-size:1.2em;
        display:inline-block;
		  }
		  .score-bubble-member-score{
		    font-size:5em;
		    width:100%;
		    font-family: HelveticaNeue-CondensedBold, 'Open Sans Condensed', Arial, sans-serif;
		    margin:15px 0 0 10px;
		    color:#143359;
		    display:inline-block;
		  }
		  
		  .score-bubble-separator{
		    clear:both;
		  }
		  
		  .score-bubble-button-wrap{
		    display:inline-block;
		    margin-top:10px;
		    clear:both;
		    bottom:10px;
		    text-align:center;
		    width:100%;
		  }
		  
		  .score-bubble-button-wrap .btn{
		    text-decoration:none !important;
		    font-size:1.3em;
		  }
    </style>
    <script type="text/javascript">
      (function($){
        $(document).ready(function(){
          $(".mc-bubble-wrap").each(function(){
		      
		      
		        $(".mc-scorecard-bubble-wrapper").on('mouseleave',function(event){
  		        $(this).hide();
  		      })
  		      $(".mc-bubble-wrap").mouseover(function(){
  		         $(".mc-scorecard-bubble-wrapper").hide();
  		         $(".mc-scorecard-bubble-wrapper", $(this)).show();
  		         
  		         var mcid = $(this).attr('data-mcid');
               
              if(!$(this).hasClass('mc-bubble-loaded')){
   		          //console.log('loading bubble for:' + mcid);	        
               
               $.ajax({
                  type: 'GET',
                   url: 'http://heritageactionscorecard.com/api/scorecard/members/congress/113/id/'+mcid+'/format/jsonp/apikey/<?php echo $hascore_member_options["scorecard_api_key"]; ?>',
                   async: true,
                   contentType: "application/json",
                   dataType: 'jsonp',
                   tryCount : 0,
                   retryLimit : 3,
                   success: function(data) {
                     var items = [];
                     $.each(data, function(key, val) {
                         items.push(val);
                     });
                     //console.log(items[0]);
                     $('.score-bubble-member-score', $(".mc-"+mcid)).html(items[0].score + "%");
                     $('.score-bubble-score-value', $(".mc-"+mcid)).html(items[0].score + "%");
                     
                     $(".mc-"+mcid).addClass('mc-bubble-loaded');
                     
  		              },
                    error : function(xhr, textStatus, errorThrown ) {
                       this.tryCount++;
                       if (this.tryCount <= this.retryLimit) {
                           //try again
                           $.ajax(this);
                           return;
                       }            
                       return;
                       if (xhr.status == 500) {
                           //handle error
                       } else {
                           //handle error
                       }
                   }
               })		        		        
     		     
  		        } 
  		      })
		      
  		    })
        })
      })(jQuery);
    </script> 
    
    <?php
    
    $output = ob_end_flush();
    return $output;
  }
  
  /**
   * Return the WP_CONTENT_URL taking into account HTTPS and the possibility that WP_CONTENT_URL may not be defined
   * 
   * @return string
   */
  public static function getWpContentUrl() {
    $wpurl = WP_CONTENT_URL;
    if(empty($wpurl)) {
      $wpurl = get_bloginfo('wpurl') . '/wp-content';
    }
    if(self::isHttps()) {
      $wpurl = str_replace('http://', 'https://', $wpurl);
    }
    return $wpurl;
  }
  
  /**
   * Detect if request occurred over HTTPS and, if so, return TRUE. Otherwise return FALSE.
   * 
   * @return boolean
   */
  public static function isHttps() {
    $isHttps = false;
    if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
      $isHttps = true;
    }
    return $isHttps;
  }
  
  /**
   * Return the WordPress URL taking into account HTTPS
   */
  public static function getWpUrl() {
    $wpurl = get_bloginfo('wpurl');
    if(self::isHttps()) {
      $wpurl = str_replace('http://', 'https://', $wpurl);
    }
    return $wpurl;
  }
  
  public static function updateMemberScores(){
    $hascore_member_options = get_option( 'hascore_member_options' );
    $members_data = json_decode(file_get_contents('http://heritageactionscorecard.com/api/scorecard/members/congress/113/format/json/apikey/'. $hascore_member_options["scorecard_api_key"] .'/'));
    foreach($members_data as $member){
      $score_members[$member->congID] = $member;
    }
    set_transient('scorecard_member_data', $score_members, 60*60*24);
  }
 
 
  //Action target that adds the "Insert Form" button to the post/page edit screen
  public static function add_form_button(){
      $is_post_edit_page = in_array(basename($_SERVER['PHP_SELF']), array('post.php', 'page.php', 'page-new.php', 'post-new.php'));
      if(!$is_post_edit_page)
          return;

      // do a version check for the new 3.5 UI
      $version    = get_bloginfo('version');

      if ($version < 3.5) {
          // show button for v 3.4 and below
          $image_btn = HASCORE_MEMBER_URL . '/images/capital-dome-sm.png';
          echo '<a href="#TB_inline?width=480&height=480&inlineId=select_mc_name" class="thickbox" id="add_mc_name" title="' . __("Insert Member of Congress", 'congresspages') . '"><img src="'.$image_btn.'" alt="' . __("Insert Member of Congress", 'congresspages') . '" /></a>';
      } else {
          // display button matching new UI
          echo '<style>.CP_media_icon{
                  background:url(' . HASCORE_MEMBER_URL . '/images/capital-dome-sm.png) no-repeat top left;
                  display: inline-block;
                  height: 16px;
                  margin: 0 2px 0 0;
                  vertical-align: text-top;
                  width: 16px;
                  }
                  .wp-core-ui a.CP_media_link{
                   padding-left: 0.4em;
                  }
               </style>
                <a href="#TB_inline?width=480&height=480&inlineId=select_mc_name" class="thickbox button CP_media_link" id="add_mc_name" title="' . __("Insert Member of Congress", 'congresspages') . '"><span class="CP_media_icon "></span> ' . __("Insert Member of Congress", "congresspages") . '</a>';
      }
  }


  //Action target that displays the popup to insert a form to a post/page
  public static function add_mc_name_popup(){
      ?>
      <script>
          function InsertMCName(){                  
              var mcid = selected_member.mcid;
              if(mcid == ""){
                  alert("<?php _e("Please select a Member of Congress", "congresspages") ?>");
                  return;
              }
              var mc_name = selected_member.name;
              var chamber = selected_member.chamber;
              window.send_to_editor("[mc_name name=\"" + mc_name + "\" chamber=\"" + chamber + "\" mcid=\"" + mcid + "\" ]");
          }

          <?php            
            if(!get_transient('scorecard_member_data')){
              HAScoreMembers::updateMemberScores();
            }
            $member_data = get_transient('scorecard_member_data');
            foreach($member_data as $member){
              $members[] = "{ value: '$member->title. ".addslashes($member->fName.' '.$member->lName)." ($member->party-$member->state)', data: '$member->congID|$member->chamber'}";
            }            
            
          ?>              
          var members_of_congress = [
            <?php echo implode($members,",\n"); ?>

          ];              
          var selected_member;

          (function($){
            $(document).ready(function(){


              $("#chamber_select").change(function(){
                // clear selects
                $(".active-selector").val('').removeClass('active-selector');
                $(".selector-"+$(this).val()).addClass('active-selector');
              })

              autoc = $('#member-autocomplete').autocomplete({
                  lookup: members_of_congress,
                  onSelect: function (suggestion) {
                      memberdata = suggestion.data.split("|");                          
                      selected_member = {name: suggestion.value, mcid: memberdata[0], chamber:memberdata[1] };
                  }
              });

              $(document).keypress(function(e) {
                  if(e.which == 13 && $("#member-autocomplete").val()!="") {
                    InsertMCName();  
                  }
              });

            })
          })(jQuery);
      </script>
      <style type="text/css" media="screen">
        .mc-selector{
          display:none;
        }
        .mc-selector.active-selector{
          display:block;
        }

        .autocomplete-suggestions { border: 1px solid #999; background: #FFF; cursor: default; overflow: auto; -webkit-box-shadow: 1px 4px 3px rgba(50, 50, 50, 0.64); -moz-box-shadow: 1px 4px 3px rgba(50, 50, 50, 0.64); box-shadow: 1px 4px 3px rgba(50, 50, 50, 0.64); }
        .autocomplete-suggestion { padding: 2px 5px; white-space: nowrap; overflow: hidden; }
        .autocomplete-selected { background: #F0F0F0; }
        .autocomplete-suggestions strong { font-weight: normal; color: #3399FF; }

        #member-autocomplete { font-size: 28px; padding: 10px; border: 1px solid #CCC; display: block; margin: 20px 0; }
        #member-autocomplete  {
          line-height:normal;
        }

      </style>
      <div id="select_mc_name" style="display:none;">
          <div class="wrap">
              <div>
                  <div style="padding:15px 15px 0 15px;">
                      <h3 style="color:#5A5A5A!important; font-family:Georgia,Times New Roman,Times,serif!important; font-size:1.8em!important; font-weight:normal!important;"><?php _e("Insert A Member of Congress", "congresspages"); ?></h3>
                      <span>
                          <?php _e("Select a Member of Congress into your post or page.", "congresspages"); ?>
                      </span>
                  </div>                      
                  <div style="padding:15px 15px 0 15px;">
                    <input type="text" name="members" id="member-autocomplete" placeholder="Type in a name...">
                  </div>

                  <div style="padding:15px;">
                      <input type="button" class="button-primary" value="Insert Member of Congress" onclick="InsertMCName();"/>&nbsp;&nbsp;&nbsp;
                      <a class="button" style="color:#bbb;" href="#" onclick="tb_remove(); return false;"><?php _e("Cancel", "congresspages"); ?></a>
                  </div>
              </div>
          </div>
      </div>

      <?php
  }

  public static function getPartyLetter($party=""){
    return strtoupper(substr($party, 0, 1));
  }
  
  public function mc_name($attrs) {
     $hascore_member_options = get_option( 'hascore_member_options' );
     if(!get_transient('scorecard_member_data')){
       HAScoreMembers::updateMemberScores();
     }
     $member_data = get_transient('scorecard_member_data');

     extract( shortcode_atts( array(
     		'chamber' => false,
     		'mcid' => false,
     		'name' => ''
     	), $attrs, 'mc_name' ) );
     	$title = "";
     	$member = false;
       switch($chamber){
         case "house":
         case "h":
         case "House":
         case "H":
           $title = "Rep. ";
           $member = $member_data[$mcid];
         break;
         case "senate":
         case "s":
         case "Senate":
         case "S":
           $title = "Sen. ";
           $member = $member_data[$mcid];
         break;        
       }
     //var_dump($member);	
     $name = $member->fName .' '. $member->lName .' '. "($member->party-".$member->state.")";
     
     if(!isset($hascore_member_options['scorecard_api_key']) || 
        empty($hascore_member_options['scorecard_api_key']) || 
        !isset($hascore_member_options['enable_popup'])){
       return $name;
     }

     
     $scorecard_member_data = $member;
     $member_image = str_replace('http://www.govtrack.us/data/photos/','',$scorecard_member_data->image_path);

     $output = "<span class='mc-bubble-wrap mc-$mcid' data-mcid='$mcid' data-score='$scorecard_member_data->score'>" . 
                 $title . $name .   
                 '<span class="mc-bubble-score">'.
                   '<span class="mc-scorecard-bubble-wrapper">'.                    
                       '<span class="score-bubble-triangle">'.
                         '<span class="score-bubble-mini-triangle"></span>'.
                       '</span>'.        
                       '<span class="score-bubble-headline">'.
                          'Heritage Action'.
                          '<span class="bubble-title">Score Card</span>'.
                       '</span>'.
                       '<span class="score-bubble-content">'.
                           '<span class="score-bubble-image">'.
                             '<img src="http://heritageactionscorecard.com/admin/memImgs/'.$member_image.'">'.
                           '</span>'.
                           '<span class="score-bubble-info">'.
                             '<span class="score-bubble-member-name">'.$title.' '.$scorecard_member_data->fName.' '.$scorecard_member_data->lName.'</span>'.
                              '<span class="score-bubble-member-score"><img src="'.HASCORE_MEMBER_URL.'/images/loading.gif" alt="'.$scorecard_member_data->score.'%"></span>'.
                           '</span>'.
                           '<span class="score-bubble-separator"></span>'.
                       '</span>'.
                       '<span class="score-bubble-button-wrap">'.
                         '<a href="http://heritageactionscorecard.com/members/member/'.$scorecard_member_data->congID.'" target="_blank" class="btn rounded gradient medium-blue-gradient">See Full Score Card</a>'.
                       '</span>'.               
                   '</span>'.
                   '<span class="score-bubble-score-value">'.$scorecard_member_data->score.'%</span>'.
                 '</span>'.
               '</span>';      

     return $output;
   }
  
}