<?php

/**
 * @file
 * Contains \Drupal\bgg_field\Plugin\field\formatter\BGGFieldDefaultFormatter.
 */
 
namespace Drupal\bgg_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'bgg_field_default_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "bgg_field_default_formatter",
 *   label = @Translation("BGG Field Formatter"),
 *   field_types = {
 *     "bgg_field"
 *   }
 * )
 */
class BGGFieldDefaultFormatter extends FormatterBase {

  const BGG_BASEURL = "https://boardgamegeek.com/";
  const BGG_API_BOARDGAME = "xmlapi2/thing/";
  const BGG_UNDOCAPI_LINKBASEURL = "https://boardgamegeek.com";
  const BGG_UNDOCAPI_BASEURL = "https://api.geekdo.com/";
  const BGG_UNDOCAPI_ALSOLIKE = "api/geekitem/recs";
  const BGG_BOARDGAME = "boardgame/";
  const BGG_DESIGNER = "boardgamedesigner/";
  const BGG_ARTIST = "boardgameartist/";
  const BGG_PUBLISHER = "boardgamepublisher/";
  const BGG_EXPANSION = "boardgameexpansion/";
  const BGG_CATEGORY = "boardgamecategory/";
  const BGG_FAMILY = "boardgamefamily/";
  const BGG_MECHANIC = "boardgamemechanic/";


  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
        
      // Set the value of the CSS color property depending on user provided
      // configuration. Individual configuration items can be accessed with
      // $this->getSetting('key') where 'key' is the same as the key in the
      // form array from settingsForm() and what's defined in the configuration
      // schema.
      //$text_color = 'inherit';
      //if ($this->getSetting('adjust_text_color')) {
      //  $text_color = $this->lightness($item->value) < 50 ? 'white' : 'black';
      //}
      //$elements[$delta] = [
      //  '#type' => 'html_tag',
      //  '#tag' => 'p',
      //  '#value' => $this->t('The content area color has been changed to @code', ['@code' => $item->value]),
      //  '#attributes' => [
      //    'style' => 'background-color: ' . $item->value . '; color: ' . $text_color,
      //  ],
      //];
      
      
        #$content = $item->value;
        $content['message'] = '';
        
        $xml_data = "";
        
        $path = \Drupal::service('extension.list.module')->getPath('bgg_field');
        $path .=  '/templates/bgg_field';
        //print($path);
        
        $httpClient = \Drupal::httpClient();
        
        $bggId = $item->value;
        
        $url = BGGFieldDefaultFormatter::BGG_BASEURL . BGGFieldDefaultFormatter::BGG_API_BOARDGAME."?id=".$bggId."&stats=1";
        $request = $httpClient->request('GET', $url);
        if ($request->getStatusCode() != 200) {
            $content['message'] = "HTTP Error in getting BGG Thing API";
            continue;
        }
        
        $url2 = BGGFieldDefaultFormatter::BGG_UNDOCAPI_BASEURL . BGGFieldDefaultFormatter::BGG_UNDOCAPI_ALSOLIKE."?objectid=".$bggId."&ajax=0&objecttype=thing&pageid=1";
        $request2 = $httpClient->request('GET', $url2);
        if ($request2->getStatusCode() != 200) {
            $content['message'] = "HTTP Error in getting BGG ALSOLIKE API";
            continue;
        }
        
		$json_data = "";
		$errorMsg = "";
		$json_obj = "";
		$gameList = [];

        $xml_data = $request->getBody()->getContents();
        $json_data = $request2->getBody()->getContents();
        $json_obj = json_decode($json_data);
        $gameList = $json_obj->{"recs"};
        

        
        if ($xml_data != "") {
            $xml = new \SimpleXMLElement($xml_data);

			$names = array();
            foreach($xml->item->name as $name) {
				$type = (string) $name["type"];
				if (!empty($type)) {
					$item = (object) array(
										"sortindex" => (int) $name["id"],
										"value" => (string) $name["value"]
										);	
					if (array_key_exists( $type, $names)) {
						$names[$type][] = $item;
					} else {
						$names[$type] = array($item);
					}	
				}	 
			}
            
            $items = array();
			foreach($xml->item->link  as $link) {
				$type = (string) $link["type"];
				if (!empty($type)) {
					$item = (object) array(
										"id" => $link["id"],
										"value" => $link["value"]
										);	
					if (array_key_exists( $type, $items)) {
						$items[$type][] = $item;
					} else {
						$items[$type] = array($item);
					}	
				}	 
			}
            
            $polls = array();
			foreach($xml->item->poll as $poll) {
				$name = (string) $poll["name"];
				if (!empty($name)) {
					if (array_key_exists( $name, $items)) {
						$polls[$name][] = $poll;
					} else {
						$polls[$name] = array($poll);
					}	
				}	 
			}
            
            // init
            $content['item'] = [];
            
            // bgg url
            $content['item']['bggUrl'] = BGGFieldDefaultFormatter::BGG_BASEURL . BGGFieldDefaultFormatter::BGG_BOARDGAME . $bggId;

            // descriptions
            $row_content = htmlspecialchars_decode ((string) $xml->item->description);
            $row_content = preg_replace_callback("/(&#[0-9]+;)/", function($m) { return mb_convert_encoding($m[1], "iso-8859-1", "HTML-ENTITIES"); }, $row_content); 
            $row_content = str_replace("\n" ,"<br>", $row_content);
            $content['item']['description'] = $row_content;
            
            // boardgamedesigner
            $content['item']['boardgamedesigner'] = [];
            if (array_key_exists("boardgamedesigner", $items)) {
                foreach ($items["boardgamedesigner"] as $item) {
                    $url = BGGFieldDefaultFormatter::BGG_BASEURL . BGGFieldDefaultFormatter::BGG_DESIGNER . $item->id;
                    $name = $item->value;
                    $content['item']['boardgamedesigner'][] = [
                        'url' => $url,
                        'name' => $name,
                    ];
                }
            }
            
            // boardgameartist
            $content['item']['boardgameartist'] = [];
            if (array_key_exists("boardgameartist", $items)) {
                foreach ($items["boardgameartist"] as $item) {
                    $url = BGGFieldDefaultFormatter::BGG_BASEURL . BGGFieldDefaultFormatter::BGG_ARTIST . $item->id;
                    $name = $item->value;
                    $content['item']['boardgameartist'][] = [
                        'url' => $url,
                        'name' => $name,
                    ];
                }
            }
            
            // boardgamepublisher
            $content['item']['boardgamepublisher'] = [];
            if (array_key_exists("boardgamepublisher", $items)) {
                foreach ($items["boardgamepublisher"] as $item) {
                    $url = BGGFieldDefaultFormatter::BGG_BASEURL . BGGFieldDefaultFormatter::BGG_PUBLISHER . $item->id;
                    $name = $item->value;
                    $content['item']['boardgamepublisher'][] = [
                        'url' => $url,
                        'name' => $name,
                    ];
                }
            }       

            // Year
            if ($xml->item->yearpublished) {
                $content['item']['yearpublished'] = (string)$xml->item->yearpublished["value"];
            }
            
            // Number of Players
            $content['item']['minplayers'] = $xml->item->minplayers["value"];
            $content['item']['maxplayers'] = $xml->item->maxplayers["value"];
            
            // Community suggested num of players
            if (array_key_exists("suggested_numplayers", $polls)) {
                $content['item']['suggested_numplayers'] = [];
                
                $suggested_numplayers = $polls["suggested_numplayers"];
                $best_player = 0;
                $recommended_player = "";
                $voters = (string) $suggested_numplayers[0]["totalvotes"];
		
                if ($voters > 0) {
                    foreach ($suggested_numplayers[0]->results as $result) {
                        //Best
                        if ($result->result[0]["numvotes"] > 0) {
                            $best_player = $result[0]["numplayers"];
                        }
                        //Recommended
                        if ((int) $result->result[1]["numvotes"] > (int) $result->result[2]["numvotes"]) {
                            $recommended_player .= ($recommended_player != "" ? ", " : "") . $result[0]["numplayers"];
                        }
                    }
                }
                $content['item']['suggested_numplayers'] = [
                    'best_player' => $best_player,
                    'recommended_player' => $recommended_player,
                    'voters' => $voters,
                ];
            }
            
            // Playing Time
            $content['item']['playingtime'] = $xml->item->playingtime["value"];
            
            // Min age
            $content['item']['minage'] = $xml->item->minage["value"];
            
            
            // Community suggested num of players
            if (array_key_exists("suggested_playerage", $polls)) {
                $content['item']['suggested_playerage'] = [];
                
                $suggested_playerage = $polls["suggested_playerage"];
                $age = 0;
                $best_vote = 0;
                $voters = (string) $suggested_playerage[0]["totalvotes"];
		
                if ($voters > 0) {
                    foreach ($suggested_playerage[0]->results->result as $result) {
                        //Best
                        if ((int) $result[0]["numvotes"] > $best_vote) {
                            $best_vote = (int) $result[0]["numvotes"];	
                            $age = (int) $result[0]["value"];							
                        }	
                    }
                }
                $content['item']['suggested_playerage'] = [
                    'age' => $age,
                    'voters' => $voters,
                ];
            }
            
            // language_dependence
            if (array_key_exists("language_dependence", $polls)) {
                $content['item']['language_dependence'] = [];
                
                //Language dependence
                $language_dependence = $polls["language_dependence"];

                $result_language = "";
                $best_vote = 0;
                $voters = (string) $language_dependence[0]["totalvotes"];
                
                if ($voters > 0) {
                    
                    foreach ($language_dependence[0]->results->result as $result) {
                        //Best
                        if ((int) $result[0]["numvotes"] > $best_vote) {
                            $best_vote = (int) $result[0]["numvotes"];	
                            $result_language = (string) $result[0]["value"];
                        }						
                    }
                    
                    // convert result to chinese
                    /*
                    if ($result_language === 'No necessary in-game text') {
                        $result_language = '遊玩時不需要閱讀文字';
                    } else if ($result_language === 'Some necessary text - easily memorized or small crib sheet') {
                        $result_language = '遊玩時需要閱讀小量文字 - 遊戲語言並非母語的玩家也能很快明白或有輔以圖示說明';
                    } else if ($result_language === 'Moderate in-game text - needs crib sheet or paste ups') {
                        $result_language = '遊玩時需要閱讀中量文字 - 遊戲語言並非母語的玩家或需要翻譯表格才能明白';
                    } else if ($result_language === 'Extensive use of text - massive conversion needed to be playable') {
                        $result_language = '遊玩時需要閱讀大量文字 - 遊戲語言並非母語的玩家需要頻繁翻譯才可遊玩';
                    } else if ($result_language === 'Unplayable in another language') {
                        $result_language = '遊戲無法以本身以外的語言遊玩';
                    }
                    */
                    
                    $content['item']['language_dependence']['result_language'] = $result_language;
                    $content['item']['language_dependence']['voters'] = $voters;
                }
            }
            
            // boardgamecategory
            $content['item']['boardgamecategory'] = [];
            if (array_key_exists("boardgamecategory", $items)) {
                foreach ($items["boardgamecategory"] as $item) {
                    $url = BGGFieldDefaultFormatter::BGG_BASEURL . BGGFieldDefaultFormatter::BGG_CATEGORY . $item->id;
                    $name = $item->value;
                    $content['item']['boardgamecategory'][] = [
                        'url' => $url,
                        'name' => $name,
                    ];
                }
            }
            
            // boardgamemechanic
            $content['item']['boardgamemechanic'] = [];
            if (array_key_exists("boardgamemechanic", $items)) {
                foreach ($items["boardgamemechanic"] as $item) {
                    $url = BGGFieldDefaultFormatter::BGG_BASEURL . BGGFieldDefaultFormatter::BGG_MECHANIC . $item->id;
                    $name = $item->value;
                    $content['item']['boardgamemechanic'][] = [
                        'url' => $url,
                        'name' => $name,
                    ];
                }
            }            
            
            // boardgameexpansion
            $content['item']['boardgameexpansion'] = [];
            if (array_key_exists("boardgameexpansion", $items)) {
                foreach ($items["boardgameexpansion"] as $item) {
                    $url = BGGFieldDefaultFormatter::BGG_BASEURL . BGGFieldDefaultFormatter::BGG_EXPANSION . $item->id;
                    $name = $item->value;
                    $content['item']['boardgameexpansion'][] = [
                        'url' => $url,
                        'name' => $name,
                    ];
                }
            }              
            
            // boardgameexpands
            $content['item']['boardgameexpands'] = [];
            if (array_key_exists("boardgameexpands", $items)) {
                foreach ($items["boardgameexpands"] as $item) {
                    $url = BGGFieldDefaultFormatter::BGG_BASEURL . BGGFieldDefaultFormatter::BGG_BOARDGAME . $item->id;
                    $name = $item->value;
                    $content['item']['boardgameexpands'][] = [
                        'url' => $url,
                        'name' => $name,
                    ];
                }
            }                       
            
            // boardgamefamily
            $content['item']['boardgamefamily'] = [];
            if (array_key_exists("boardgamefamily", $items)) {
                foreach ($items["boardgamefamily"] as $item) {
                    $url = BGGFieldDefaultFormatter::BGG_BASEURL . BGGFieldDefaultFormatter::BGG_FAMILY . $item->id;
                    $name = $item->value;
                    $content['item']['boardgamefamily'][] = [
                        'url' => $url,
                        'name' => $name,
                    ];
                }
            }                                   
            
            // primary
            if (array_key_exists("primary", $names)) {
                $content['item']['primary'] = $names["primary"][0]->value;
            }
            
            // alternate
            $content['item']['alternate'] = [];
            if (array_key_exists("alternate", $names)) {
                foreach ($names["alternate"] as $item) {
                    $content['item']['alternate'][] = $item->value;
                }
            }     
            
            // avgWeight
            $avgWeight = $xml->item->statistics->ratings->averageweight["value"];
            if ($avgWeight && $avgWeight != 0) {
                $content['item']['avgWeight'] = $avgWeight;
            }
    
            
            // avgRank
            $avgRank = $xml->item->statistics->ratings->average["value"];
            if ($avgRank && $avgRank != 0) {
                $content['item']['avgRank'] = $avgRank;
            }
            
            // Handle also like
            if (is_array($gameList) && count($gameList) > 0) {
                
                $content['item']['alsoLike'] = [];
                
                foreach ($gameList as $game) {
                    
                    
                    
                    $gameName = $game->{'item'}->{'name'};
                    $gameDesc = $game->{'description'};
                    
                    $gameImage = $game->{'image'}->{'src'};
                    $gameLink = BGGFieldDefaultFormatter::BGG_UNDOCAPI_LINKBASEURL . $game->{'item'}->{'href'};
                    $rating = $game->{'rating'};
                    $ranking = $game->{'rank'};
                    $yearOfPublish = $game->{'yearpublished'};
                    $numOfFans = $game->{'numfans'};
                    
                    
                    
                    $gameMeta = "";
                    if ($rating == 0) {
                        $gameMeta .= 'Rating: NA <br>';	
                    } else {
                        $gameMeta .= 'Rating： '.round($rating, 2).' / 10.00 <br>';	
                    }
                    
                    if ($ranking == 0) {
                        $gameMeta .= 'Ranking: NA <br>';	
                    } else {
                        $gameMeta .= 'Ranking: '.$ranking.'<br>';	
                    }
                    
                    if ($yearOfPublish == "") {
                        $gameMeta .= 'Year published: NA <br>';	
                    } else {
                        $gameMeta .= 'Year published: '.$yearOfPublish.'<br>';	
                    }            
                    
                    if ($numOfFans == "") {
                        $gameMeta .= 'Num of BGG Fans: NA <br>';	
                    } else {
                        $gameMeta .= 'Num of BGG Fans: '.$numOfFans.'<br>';	
                    }   
                    
                    $content['item']['alsoLike'][] = [
                        'gameLink' => $gameLink,
                        'gameImage' => $gameImage,
                        'gameName' => $gameName,
                        'gameDesc' => $gameDesc,
                        'gameMeta' => $gameMeta,
                    ];
            
                }
            }
            
            
            
            
        }

    
        
        $source = [
            '#theme' => 'bgg_field',
            '#content' => $content,
        ];
        $elements[$delta] = [
            '#markup' => \Drupal::service('renderer')->render($source)
        ];
    
      
    }
    
    
    return $elements;
  }

  /**
   * {@inheritdoc}
   *
   * Set the default values for the formatter's configuration.
   */
  public static function defaultSettings() {
    // The keys of this array should match the form element names in
    // settingsForm(), and the schema defined in
    // config/schema/field_example.schema.yml.
    /*
    return [
        'adjust_text_color' => TRUE,
      ] + parent::defaultSettings();
      */
    return parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   *
   * Define the Form API widgets a user should see when configuring the
   * formatter. These are displayed when a user clicks the gear icon in the row
   * for a formatter on the manage display page.
   *
   * The field_ui module takes care of handling submitted form values.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Create a new array with one or more form elements. $form is available for
    // context but you should not add your elements to it directly.
    $elements = [];

    // The keys of the array, 'adjust_text_color' in this case, should match
    // what is defined in ::defaultSettings(), and the field_example.schema.yml
    // schema. The values collected by the form will be automatically stored
    // as part of the field instance configuration so you do not need to
    // implement form submission processing.
    
    /*
    $elements['adjust_text_color'] = [
      '#type' => 'checkbox',
      // The current configuration for this setting for the field instance can
      // be accessed via $this->getSetting().
      '#default_value' => $this->getSetting('adjust_text_color'),
      '#title' => $this->t('Adjust foreground text color'),
      '#description' => $this->t('Switch the foreground color between black and white depending on lightness of the background color.'),
    ];
    */
    
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    // This optional summary text is displayed on the manage displayed in place
    // of the formatter configuration form when the form is closed. You'll
    // usually see it in the list of fields on the manage display page where
    // this formatter is used.
    //$state = $this->getSetting('adjust_text_color') ? $this->t('yes') : $this->t('no');
    //$summary[] = $this->t('Adjust text color: @state', ['@state' => $state]);
    
    $summary = [];
    
    return $summary;
  }

}
