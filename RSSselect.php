<?php
/**
 * MediaWiki RSS Select Extension
 * {{php}}{{Category:Extensions|RSSselect}}
 * @package MediaWiki
 * @subpackage Extensions
 * @licence GNU General Public Licence 2.0 or later
 */
 
define('RSSSELECT_VERSION','0.6');
 
$wgExtensionFunctions[] = 'wfSetupRSSselect';
$wgHooks['LanguageGetMagic'][] = 'wfRSSselectLanguageGetMagic';



 
$wgExtensionCredits['parserhook'][] = array(
        'name'        => 'RSS SELECT',
        'author'      => 'Daniel Yount - icarusfactor - factorf2@yahoo.com',
        'description' => 'RSS parser with filter word and starts with extension',
        'url'         => 'http://www.mediawiki.org',
        'version'     => RSSSELECT_VERSION
);
 
function wfRSSselectLanguageGetMagic(&$magicWords,$langCode = 0) {
        $magicWords['rssselect'] = array(0,'rssselect');
        return true;
}
 
function wfSetupRSSselect() {
        global $wgParser;
        $wgParser->setFunctionHook('rssselect','wfRenderRSSselect');
        return true;
}
 
# Renders a table of all the individual month tables
function wfRenderRSSselect(  &$parser ) {
        $output = '';        
        $control_start=0;
        $control_has=0;
        $itemcount=0;
        $rsstitle      = array(); 
        $rsssubject = array(); 
        $resultrss = array();  //and OR array if any item finds match markit and it will be posted to output.
        $arr_count=0;

        
        #$parser->mOutput->mCacheTime = -1;
        $argv = array();
        foreach (func_get_args() as $arg) if (!is_object($arg)) {
                if (preg_match('/^(.+?)\\s*=\\s*(.+)$/',$arg,$match)) $argv[$match[1]]=$match[2];
        }

       
              
        if (isset($argv['feed']))           $feeditem  = $argv['feed']; 
        if (isset($argv['startswith']))   { $control_start = 1;$starts  = $argv['startswith'];  }
        if (isset($argv['has']))             { $control_has   = 1;$hasthis = $argv['has']; }
        if (isset($argv['max']))           { $maxitems = $argv['max']; } else {$maxitems=1;}
        if (isset($argv['type']))           { $thetype = $argv['type']; } else {$thetype="title";}        
        //scan description for key value pair. 
        if (isset($argv['delim']))         { $thedelim = $argv['delim']; } else {$thedelim="=";}
        if (isset($argv['key']))            { $thekey = $argv['key']; } 
        if (isset($argv['keycount']))    { $thekeycount = $argv['keycount']; } 

        //other options "nothave" "striphtml"
        //return $feeditem;    

       //$feed = str_replace( ' ' , '%20' , urlencode( $feed ) );

        //require_once('autoloader.php');
 
        // We'll process this feed with all of the default options.
        $feed = new SimplePie();
 
        // Set which feed to process.
        $feed->set_feed_url( $feeditem );
        //$feed->set_feed_url(  "http://en.wikipedia.org/w/index.php?title=Template:Latest_stable_software_release/Debian&feed=rss&action=history"  );
        // Run SimplePie.
        $feed->init();


       
       

        //Get array items count    
        $arr_count = count( $rsstitle );   


         // Load DB into local RSS array.
        foreach ($feed->get_items() as $item): 
          $rsstitle[   $itemcount ] =  $item->get_title(); 
          $rsssubject[ $itemcount ] =  $item->get_description(true);
          //$rssdate[    $itemcount ] =  $item->get_date( 'l jS \of F Y h:i:s A' );
          //if ($enclosure = $item->get_enclosure())
	  //{
	  // $rsslink[   $itemcount  ] = $enclosure->get_link();
	  //}          
          //else
          //{
          // $rsslink[    $itemcount ] =  $item->get_link();
          //} 

          $itemcount++;
        endforeach;
         
        //Get array items count    
        $arr_count = count( $rsstitle );  




      if( $control_start == 1 ) {
                                 $itemcount = 0;
                                 while( $itemcount <= $arr_count )
                                  {
                                   $pattern = '/^'.$starts.'/';
                                   preg_match($pattern, $rsstitle[$itemcount] , $matches);                                                               
                                   // add tick to item array to know if match was found here.                                  
                                   if( $matches == false ) { $resultrss[$itemcount] = 1; }
                                   $itemcount++;
                                  }                      

                                }

      if( $control_has == 1 ) {
                                $itemcount = 0;
                                while( $itemcount <= $arr_count )
                                  {
                                   $pattern = '/'.$hasthis.'/';
                                   preg_match($pattern, $rsstitle[$itemcount] , $matches);                                                                   
                                   // add tick to item array to know if match was found here. 
                                   //check if has been marked off list already.                                 
                                   if( $matches == false ) { $resultrss[$itemcount] = 1; }                                  
                                   $itemcount++;
                                  }                      
                              
                              }

       //Now run array to find what items are tick marked and post them to output.    
       $itemcount = 0;
       $maxcount=0;
 
       while( $itemcount <= $arr_count )
            {  
            

             if( $resultrss[ $itemcount ] == 0 )
               {
                    if( $maxcount >= $maxitems ) { break; }

                    if(  !strcmp( $thetype , "title" )) { $output .= $rsstitle[$itemcount]; }

// strip key/value form description. 
// 1: Search description for key  [x]
// 2: If matches                         [x]
// 3; split data into lines.            [x]
// 3: Split data at key /delimiter  [  ]
// 4: read data until end of line   [  ]
// 5: strip out html                    [x] 
// 6: return data.

                    if(  !strcmp( $thetype , "description" ))
                                  { 
                                    //search and pull out keyvalue pair 
                                     if (isset($argv['key']))
                                      {  
                                       $pattern = '/'.$thekey.'/';
                                       $pattern2 = '/'.$thedelim.'/';
                                        preg_match($pattern, $rsssubject[$itemcount] , $matches);
                                        if( $matches ) {
                                                                $chars = preg_split('/ /', $rsssubject[$itemcount] , -1, PREG_SPLIT_OFFSET_CAPTURE);  
                                                                //$output .= $rsssubject[$itemcount];
                                                                //$output .= print_r( $chars[38][0] );
                                                                //$mnw=0;  

                                                                //$output .= $chars[38][0];
                                                                $count = sizeof( $chars );
                                                                $keyset    =0;
                                                                $delimset =0; 
                                                                $reset=0;
                                                                for($i = 0; $i < $count; $i++)
                                                                  {                                                                     
                                                                     $segmentvalue = strip_tags ( $chars[$i][0] ).'&nbsp;' ;  
                                                                     //serch key value and set                                                                     
                                                                    
                                                                     if( ($delimset==1) && ($keyset==1)  ){ 
                                                                                                  if( $thekeycount == $reset   ) { $keyset=0;$delimset=0; }
                                                                                                  $output .= $segmentvalue;  
                                                                                                   //reset key value data.                                                                                                  
                                                                                                   $reset++;                                                                                 
                                                                                                 }

                                                                     preg_match($pattern2, $segmentvalue , $matches3);
                                                                     if( $matches3 && ($keyset==1) && ($reset==0)  ){                                                                                             
                                                                                                                       $delimset=1;
                                                                                                                       //$output .= $segmentvalue;                                                                                        
                                                                                                                      }
                                                                     preg_match($pattern, $segmentvalue , $matches2);
                                                                     if( $matches2 && ($reset==0)  ){                                                                                             
                                                                                             $keyset=1;
                                                                                             //$output .= $segmentvalue;                                                                                                                                                                                   
                                                                                            }
                                                                     
                                                                     //$output .= $segmentvalue;                                                       

                                                                 }
                                                                 
                                                                }


                                      } 
                                     else { $output .= $rsssubject[$itemcount]; } // if not key valuepair set just output all the descript.
                                  }


                    $maxcount++; 
               }
             $itemcount++;
            }            
       

       //$locate = str_replace( ' ' , '%20' , urlencode( $locate ) );

       return $parser->insertStripItem( $output, $parser->mStripState );
       #return $output;


}
 
	
