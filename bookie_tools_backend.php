<?php
	// ==============================================================================================================
	// dev notes (15/8/15):
	// - edited extract_hills_scorer_names(); it's now identical to extract_hills_scorer_names_and_prices, except it
	// doesn't include the prices in the table. Also - beneath the list of players for both teams, it builds and
	// includes a series of wincast strings for pasting into wincast markets in Event Builder
	// ==============================================================================================================
	// dev notes (3/8/15):
	// - added "Hills - extract goalscorer names and prices (paste text)" function
	// ==============================================================================================================
	// dev notes (21/5/15):
	// - added function to scrape dog results from www.igb.ie
	// - wrote wrapper for scrape_igb_dot_ie_dogs() so that the user can paste the html for multiple dog result pages
	//   and scrape the results or all of the pages/meetings
	// - wrote wrapper for scrape_gbgb_dot_org_dot_uk_results() so that the user can paste the html for multiple dog
	//   result pages and scrape the results for all of the pages/meetings
	// ==============================================================================================================
	// dev notes (14/5/15):
	// added function to extract dog results from results pages on www.igb.ie
	// ==============================================================================================================
	// dev notes (1/1/15):
	// added function to scrape names from an oddschecker paste; function takes a parameter that indicates whether to
	// replace the final space in the name with a comma or not - this can be used for formatting participants for
	// participant loader
	// ==============================================================================================================
	// dev notes (4/1/14):
	// 1. Fix BBC football fixture scraper so that it scrapes days
	// ==============================================================================================================
	// dev notes (3/1/14):
	// Current work:
	// 1. Fix html table for hills football players - team lists appear at different heights
	//    (DONE - fixed with vertical alignment style tag - 3/1/14)
	// 2. Write BBC football fixture extracter
	// 3. Fix multiple error messages (suppressed on online webpage but present on local development server)
	//    (DONE - 3/1/14)
	// 4. Only print the scorers in a table - omit the long list running in a single column down the page
	//    (DONE - 3/1/14)
	// ==============================================================================================================
	// dev notes (30/1/14):
	// three functions finished:
	// 1. extract Hills football names
	// 2. extract Hills tennis names
	// 3. extract Turf TV american horse meeting names
	// ==============================================================================================================

	// code to handle text submitted from "tools.htm" form
	// redirects text to appropriate function below, according to what option the user has chosen
	// in "tools.htm"
	// (e.g. Extract Hills Goalscorer, Extract Hills Tennis Names, Extract Turf TV Names)
	
	// first, get the user's tool choice and text from the form
	// $hills_page_text = $_POST['hills_page_text'];
	$tool = $_POST['tool_choice'];
	$text = $_POST['text'];
	
	// if log file exists from previous run, delete it
	if (file_exists("log_file.txt")) {
		unlink("log_file.txt");
	}
	
	// next, send the text to the appropriate function for processing
	if ($tool == "extract_hills_scorer_names") {
		$write_text = extract_hills_scorer_names($text);
		$write_text = create_table_from_hills_scorer_names($write_text);
	}
	if ($tool == "extract_hills_scorer_names_and_prices") {
		$write_text = extract_hills_scorer_names_and_prices($text);
	}
	if ($tool == "extract_hills_tennis_names") {
		$write_text = extract_hills_tennis_names($text);
	}
	if ($tool == "extract_turf_tv_american_meeting_names") {
		$write_text = extract_turf_tv_american_meeting_names($text);
	}
	if ($tool == "extract_bbc_football_fixtures") {
		$write_text = extract_bbc_football_fixtures($text);
		file_put_contents("bbc_football_fixtures.csv", $write_text);
		send_file_to_user("bbc_football_fixtures.csv");
	}
	if ($tool == "scrape_oddschecker_fixtures") {
		$write_text = scrape_oddschecker_fixtures($text);
		log_write("\nBack in main program, write_text is:\n$write_text");
		file_put_contents("oddschecker_football_fixtures.csv", $write_text);
		send_file_to_user("oddschecker_football_fixtures.csv"); // this function is already written (see 5 lines above!!!)
	}
	if ($tool == "format_names") {
		$write_text = format_names_for_participant_loader($text);
	}
	if ($tool == "extract_boylesports_goalscorer_names") {
		$write_text = extract_boylesports_goalscorer_names($text);
	}
	// new 1/1/15:
	if ($tool == "oddschecker_extract_names_add_comma") {
		$write_text = oddschecker_extract_names_from_text_paste($text, 1);
	}
	if ($tool == "oddschecker_extract_names_dont_add_comma") {
		$write_text = oddschecker_extract_names_from_text_paste($text, 0);
	}
	if ($tool == "oddschecker_extract_3_balls") {
		$write_text = oddschecker_extract_3_balls($text);
	}
	if ($tool == "scrape_gbgb_dog_results") {
		$write_text = multi_scrape_gbgb_dot_org_dog_results_wrapper($text);
	}
	
	if ($tool == "first_scorers_to_anytime_scorers") {
		$write_text = first_scorers_to_anytime_scorers($text);
	}
	
	if ($tool == "scrape_igb_dot_ie_dogs") {
		$write_text = multi_scrape_igb_dot_ie_dog_results_wrapper($text);
	}
	
	// log user details here
	log_user_details($tool, $text);
	
	// construct a webpage that displays the contents of $write_text here
	echo '<html><head></head><body style="font-family:Courier">';
	// echo "<pre>";
	echo $write_text;
	// echo "</pre>";
	echo "</body></html>";

// ===========================================================================================
// Function to implement logging for Amelyn bookie tools
// For each use, we're going to keep:
// 1. The user ip address
// 2. The user-agent (i.e. web browser) the user is using
// 3. The time and date at which the user used Amelyn bookie tools
// 4. The tool the user has selected
// 5. Either:
//    - the entire text submitted by the user (if it's under a thousand characters long)
//    - the first thousand characters of the text submitted
// 6. If the text submitted by the user contains <title> and </title> tags, extract the title
// All of the log files are kept in a folder named 'bookie_tools_logs', which is stored at the
// same level as the bookie tools program itself
//
// The logs for each day are stored in a separate file.
// The logs for Monday 28th September 2015, for example, will be stored in a file named
// '15_09_28_bookie_tools_log.txt' (format is 'YEAR_MONTH_DAY_bookie_tools_log.txt')
// =============================================================================================
function log_user_details($tool, $text) {
  
  $endl = "<br>\n";

  // build a filename for today's log in the format 'YEAR_MONTH_DAY_bookie_tools_log.txt'
  $log_filename = '../bookie_tools_logs/'.date('y_m_d').'_bookie_tools_log.txt';
  // echo "log_filename = $log_filename".$endl;
  
  // if $text contains <title> and </title> tags, extract the title string (using the
  // extract_inner_text($page_code, $start_sentinel, $end_sentinel) function)
  $title_text = '';
  if ((strpos($text, '<title>') !== false) && (strpos($text, '</title>') !== false)) {
    $title_text = extract_inner_text($text, '<title>', '</title>');
    $title_text = trim($title_text);
  }
  // echo "title_text = $title_text".$endl;

  // build a write_string containing all the information we wish to log
  $log_string = "\n==================== <<{{new_log_entry}}>> ====================";
  // get timedate string, append it
  $time_date_string = date(DATE_RFC850);
  $log_string .= "\nTimedate:\n".$time_date_string;
  // append tool
  $log_string .= "\nTool:\n".$tool."\nTitle Text:\n".$title_text;
  // get user ip address, append it
  $user_ip = $_SERVER['REMOTE_ADDR'];
  $log_string .= "\nUser IP:\n".$user_ip;
  // get user remote host, append it
  $user_remote_host = $_SERVER['REMOTE_HOST'];
  $log_string .= "\nUser Remote Host:\n".$user_remote_host;
  $user_agent = $_SERVER['HTTP_USER_AGENT'];
  $log_string .= "\nUser agent:\n".$user_agent;
  // append title text
  $log_string .= "\nTitle tag text:\n".$title_text;
  // get first thousand characters of user submitted text (or full submitted text if length is
  // less than 1000 characters), append it
  if (strlen($text) > 1000) {
    $text = substr($text, 0, 1000);
  }
  $log_string .= "\nUser submitted text:\n".$text;
  
  // if the log file for the current day doesn't exist, create it and immediately chmod it to 0600
  if (!file_exists($log_filename)) {
    $new_log_file = fopen($log_filename, 'a');
    fwrite($new_log_file, "\n");
    fclose($new_log_file);
    chmod($log_filename, 0600);
  }
  
  // write or append the write string to todays log file (depending on $file_mode)
  // use file mode 'a' to write/append automatically
  $log_file = fopen($log_filename, 'a');
  fwrite($log_file, $log_string);
  fclose($log_file);
  
  return;
}
	// ==================================================================================================
	// This function takes a number of target urls for the www.igb.ie website as input
	// It then downloads all the pages, extracts all the results for each page
	function multi_scrape_igb_dot_ie_dog_results_wrapper($target_urls) {
	
		$pages_code = '';
		
		$target_urls = trim($target_urls);
		$target_urls = explode("\n", $target_urls);
	
		// loop through $target_urls, downloading each webpage and appending the html for the page to $pages_code
		foreach ($target_urls as $current_target_url) {
			$current_target_url = trim($current_target_url);
			$pages_code .= file_get_contents($current_target_url);
		}

		// explode along '<!DOCTYPE html>'
		$html_for_pages = explode('<!DOCTYPE html>', $pages_code);
		
		// remove first element, which doesn't contain code for a results page
		array_shift($html_for_pages);
		
		$write_string = '';
		
		// loop through the html for each results page in the $html_for_pages array
		// for each page, extract there results and append them to $write_string
		foreach ($html_for_pages as $current_html) {
			$current_meeting_results = scrape_igb_dot_ie_dogs($current_html);
			$write_string .= $current_meeting_results;
		}
		
		return $write_string;
	}
	// ==================================================================================================================
	function scrape_igb_dot_ie_dogs($page_code) {
	
		$write_string = ''; // build results in this string
		
		// scrape date string; it begins with '<span id="Content_Content_MainSiteContent_MainContent_PageHeader"><b>'
		// and ends with '</b>'
		$start_sentinel = '<span id="Content_Content_MainSiteContent_MainContent_PageHeader"><b>';
		$end_sentinel = '</b>';
		$date_and_meeting_string = extract_inner_text($page_code, $start_sentinel, $end_sentinel);
		// echo "date_and_meeting_string = $date_and_meeting_string<br>";
		// $date_and_meeting_string is in the format: '15-May-15 Results for Curraheen Park:'
		// extract meeting from $date_and_meeting_string
		$start = strpos($date_and_meeting_string, 'Results for ') + 12;
		$meeting = substr($date_and_meeting_string, $start);
		// remove ':' from end of $meeting
		$meeting_string_length = strlen($meeting);
		$meeting = substr($meeting, 0, --$meeting_string_length);
		// test print
		// echo "meeting = $meeting<br>\n";
		// extract date_string from $date_and_meeting_string
		$end = strpos($date_and_meeting_string, ' Results for');
		$date_string = substr($date_and_meeting_string, 0, $end);
		// test print
		// echo "date_string = $date_string<br>\n";
		// parse the date into day, month, year
		$date_elements = explode('-', $date_string);
		// rebuild $date_string in the format '15 May 2015'
		$day_no = $date_elements[0];
		$month = $date_elements[1];
		$year = $date_elements[2];
		$year = '20'.$year;
		$day_suffixes = array('', 'st', 'nd', 'rd');
		// work out what the appropriate day suffix is
		$int_day = (int)$day_no;
		if ($int_day < 4) {
			$day_suffix = $day_suffixes[$int_day];
		} else {
			$day_suffix = 'th';
		}
		$date_string = $day_no.$day_suffix.' '.$month.' '.$year;
		// test print
		// echo "rebuilt date_string = $date_string<br>\n";
		
		// extract all the code blocks, each of which contains the full result for a single dog race
		// each code block begins with '<div class="col-12 clearfix race-heading">'
		// explode the page along this sentinel string
		$code_blocks = explode('<div class="col-12 clearfix race-heading">', $page_code);
		// the first code block doesn't contain race result information; discard it
		// $code_blocks = array_shift($code_blocks);
		array_shift($code_blocks);
		$total_code_blocks = count($code_blocks);
		// echo "total_code_blocks = $total_code_blocks<br>";
		// echo "code_blocks:<br>";
		// print_r($code_blocks);
		
		// loop through the code blocks, extracting the race result from each block
		// the race result will be an array in the form:
		// {
		//		'race_name'	=> '19:31 Romford',
		//		'dogs'		=> < an array containing all the dog arrays>
		// }
		//
		// each dog within the dog array will be represented by an array in the form:
		// {
		//		'position'		=> '1st',
		//		'trap_no'		=> 6,
		//		'name'			=> 'Gem Twist',
		//		'price_string'	=> '13/8F'
		// }
		$race_results = array(); // place all race results in this array
		foreach ($code_blocks as $current_code_block) {
		
			// extract race name
			$race_name = extract_inner_text($current_code_block, '<div class="col-11">', '</div>');
			$race_name = trim($race_name);
			// get rid of &nbsp in race name
			$race_name = str_replace('&nbsp;&nbsp;&nbsp;', ' ', $race_name);
			// get rid of colon at end of race name
			$race_name_length = strlen($race_name);
			if (substr($race_name, $race_name_length - 1, 1) == ';') {
				$race_name = substr($race_name, 0, $race_name_length - 1);
			}
			// echo "race_name after nbsp and colon replacement is:<br>\n";
			// echo "race_name = $race_name<br>";
			// get small race name
			$race_name_elements = explode('-', $race_name);
			$small_race_name = trim($race_name_elements[0]);
			
			// extract all the code blocks containing dog details
			// each dog code block begins with '<tr>    <td vAlign="top"' and ends with '</tr>'
			$dog_code_blocks = explode('<tr>    <td vAlign="top"', $current_code_block);
			// test print dog_code_blocks
			// echo "=============================================================================================<br>\n";
			// echo "dog_code_blocks:<br>\n";
			// print_r($dog_code_blocks);
			// echo "=============================================================================================<br>\n";
			// remove the first dog code block, which doesn't contain dog information
			array_shift($dog_code_blocks);
			
			// loop through the dog code blocks, extracting position, trap_no, name and price_string
			$all_dogs_for_current_race = array();
			foreach ($dog_code_blocks as $current_dog_code_block) {
				// explode along '<td ' to get code for the separate columns
				$column_code_blocks = explode('<td ', $current_dog_code_block);
				// test print column_code_blocks
				// echo "===================================================================<br>\n";
				// echo "column_code_blocks:<br>\n";
				// print_r($column_code_blocks);
				// echo "<br>\n";
				// echo "===================================================================<br>\n";
				
				// extract position; contained in $column_code_blocks[0];
				$position = extract_inner_text($column_code_blocks[0], '>', '</td>');
				$pos_string_length = strlen($position);
				$position = substr($position, 0, $pos_string_length - 1);
				// echo "position = $position<br>\n";
				// append the appropriate suffix to position
				$position_suffixes = array('', 'st', 'nd', 'rd',);
				$int_pos = (int)$position;
				if ($int_pos < 4) {
					$position .= $position_suffixes[$int_pos];
				} else {
					$position .= 'th';
				}
				
				// extract trap no; contained in $column_code_blocks[1]
				$trap_no = extract_inner_text($column_code_blocks[1], 'alt="', '"');
				$elements = explode(' ', $trap_no);
				$trap_no = $elements[1];
				$trap_no = (int)$trap_no;
				// echo "trap_no = $trap_no<br>\n";
				
				// extract dog name; contained in $column_code_blocks[2]
				$dog_name_code_block = $column_code_blocks[2];
				// move to '</a>'; this marks the end of the dog name
				$end = strpos($dog_name_code_block, '</a>');
				// move back to '>'; this marks the beginning of the dog name
				$pos = $end--;
				while (substr($dog_name_code_block, $pos, 1) != '>') {
					$pos--;
				}
				$pos++;
				$dog_name_length = $end - $pos + 1;
				$dog_name = substr($dog_name_code_block, $pos, $dog_name_length);
				// echo "dog_name = $dog_name<br>\n";
				
				// extract price_string; contained in $column_code_blocks[9]
				$price_string = extract_inner_text($column_code_blocks[9], '>', '</td>');
				// echo "price_string = $price_string<br>\n";
				
				// for each dog, create a dog array, append it to $current_dog_arrays
				$current_dog_array = array(
										'position'		=> $position,
										'trap_no'		=> $trap_no,
										'name'			=> $dog_name,
										'price_string'	=> $price_string
											);
				$all_dogs_for_current_race[] = $current_dog_array;
			}
			// form a race result array, append it to the array $race_results
			$current_race_result = array(
									'race_name'			=> $race_name,
									'small_race_name'	=> $small_race_name,
									'dogs'				=> $all_dogs_for_current_race
										);
			$race_results[] = $current_race_result;
		}
	
		// now that we've got all the dog race results in $race_results, form a write_string in the following form:
		// ===================================
		// Race results for Romford (15/05/15)
		// ===================================
		//
		// 19:31 Romford
		// 1st T6 Gem Twist (11/4)
		// 2nd T3 Marinas Joe (9/2)
		// 3rd T4 Confident Cait (7/2)
		// 4th T1 Droopys Mazda (13/8F)
		// 5th T5 Keleegar Hondo (10/1)
		//
		// 19:46 Romford
		// 1st T2 Orange Bill (8/1)
		// 2nd T4 Mash Mad Rumble (2/1F)
		// 3rd T3 Luminous Queen (7/2)
		// 4th T6 King Grant (7/1)
		// 5th T1 Young Tyrur (9/2)
		// 6th T5 Aero Ace (6/1)
		// etc.
		
		$write_string = '===================================<br>';
		$write_string .= 'Race results for '.$meeting.' ('.$date_string.')<br>';
		$write_string .= '===================================<br>';
	
		// loop through $race_results; for each race result, build a race_write_string, then append this to $write_string
		foreach ($race_results as $current_race_result) {
			$race_write_string = '<br>';
			$race_write_string .= $current_race_result['small_race_name'];
			$race_write_string .= '<br>';
			$dogs_in_current_race = $current_race_result['dogs'];
			foreach ($dogs_in_current_race as $current_dog) {
				$race_write_string .= $current_dog['position'];
				$race_write_string .= ' ';
				$race_write_string .= 'T';
				$race_write_string .= $current_dog['trap_no'];
				$race_write_string .= ' ';
				$race_write_string .= $current_dog['name'];
				$race_write_string .= ' (';
				$race_write_string .= $current_dog['price_string'];
				$race_write_string .= ')<br>';
			}
			$write_string .= $race_write_string;
		}
		$write_string .= '<br>';

		return $write_string;
	}
	// ==================================================================================================================
	// extracts the substring from $big_text that begins with $start_sentinel and ends with $end_sentinel
	// n.b. $start_sentinel and $end_sentinel are omitted from the extracted text
	function extract_inner_text($big_text, $start_sentinel, $end_sentinel) {
	
		$extracted_text = '';
	
		$start = strpos($big_text, $start_sentinel) + strlen($start_sentinel);
		$big_text = substr($big_text, $start);
		$end = strpos($big_text, $end_sentinel);
		$extracted_text = substr($big_text, 0, $end);
		// echo "extracted_text = $extracted_text";
	
		return $extracted_text;
	}
	// ==================================================================================================================
	function first_scorers_to_anytime_scorers($text) {
	
		// remove everything before the beginning of the scorers
		$sentinel = '-------------------------------------------------';
		$start =  strpos($text, $sentinel) + strlen($sentinel);
		$text = substr($text, $start);
	
		$text = trim($text);
		
		// loop through the lines, for each scorer form an array containing the scorer name and first scorer price
		$all_scorers = array();
		$no_scorer_and_own_goal = array(); // append no scorer and own goal arrays to this array
		$lines = explode("\n", $text);
		foreach ($lines as $line) {
			$elements = explode("\t", $line);
			$price = trim($elements[0]);
			// if there's no slash in the price, append '/1'
			if (strpos($price, "-") === false) {
				$price = $price.'/1';
			}
			// if there's a dash in the price, replace it with a slash
			if (strpos($price, '-') !== false) {
				$price = str_replace('-', '/', $price);
			}
			$scorer = trim($elements[1]);
			$current_scorer = array(
							'scorer'				=> $scorer,
							'first_scorer_price'	=> $price
									);
			
			// only append the scorer if it isn't 'No Goalscorer' or 'Own, Goal'
			$keep_this_scorer = 1;
			if (($scorer == 'No Goalscorer') || ($scorer == 'Own, Goal')) {
				$keep_this_scorer = 0;
			}
			if ($keep_this_scorer) {
				$all_scorers[] = $current_scorer;
			} else {
				$no_scorer_and_own_goal[] = $current_scorer;
			}
		}
		
		// prepend no scorer and own goal to all_scorers
		$all_scorers = array_merge($no_scorer_and_own_goal, $all_scorers);
		
		
		// test print
		// echo "all_scorers<br>\n";
		// print_r($all_scorers);
		// echo "no_scorer_and_own_goal<br>\n";
		// print_r($no_scorer_and_own_goal);
	
		// scorer conversion prices; first scorer and anytime scorer prices
		$price_conversions = array(
								'11/8'	=> '1/4',
								'6/4'	=> '2/7',
								'29/20'	=> '2/7',	// 29/20 = 6/4
								'13/8'	=> '1/3',
								'7/4'	=> '2/5',
								'15/8'	=> '4/9',
								'2/1'	=> '1/2',
								'9/4'	=> '4/7',
								'5/2'	=> '8/13',
								'11/4'	=> '4/6',
								'14/5'	=> '8/11',
								'3/1'	=> '4/5',
								'10/3'	=> '10/11',
								'7/2'	=> '1/1',
								'4/1'	=> '5/4',
								'9/2'	=> '11/8',
								'5/1'	=> '6/4',
								'11/2'	=> '13/8',
								'6/1'	=> '7/4',
								'13/2'	=> '15/8',
								'7/1'	=> '2/1',
								'15/2'	=> '9/4',
								'8/1'	=> '5/2',
								'17/2'	=> '11/4',
								'9/1'	=> '3/1',
								'19/2'	=> '10/3',
								'10/1'	=> '7/2',
								'11/1'	=> '4/1',
								'12/1'	=> '9/2',
								'14/1'	=> '5/1',
								'16/1'	=> '11/2',
								'18/1'	=> '13/2',
								'20/1'	=> '7/1',
								'22/1'	=> '8/1',
								'25/1'	=> '9/1',
								'28/1'	=> '10/1',
								'30/1'  => '11/1',
								'33/1'	=> '12/1',
								'40/1'	=> '14/1',
								'50/1'	=> '18/1',
								'60/1'  => '22/1',
								'66/1'	=> '25/1',
								'75/1'  => '28/1',
								'80/1'	=> '33/1',
								'100/1'	=> '40/1',
								'125/1'	=> '50/1',
								'150/1'	=> '66/1'
									);
		
		// loop through $all_scorers, work out price for anytime scorer and add it to each scorer
		$total_scorers = count($all_scorers);
		for ($i = 0; $i < $total_scorers; $i++) {
			$current_scorer = $all_scorers[$i];
			$first_scorer_price = $current_scorer['first_scorer_price'];
			$anytime_scorer_price = '';
			// try to find a matching anytime price in $price_conversions
			$price_conversion_keys = array_keys($price_conversions);
			if (in_array($first_scorer_price, $price_conversion_keys)) {
				$anytime_scorer_price = $price_conversions[$first_scorer_price];
			}
			$current_scorer['anytime_scorer_price'] = $anytime_scorer_price;
			// if we're at 'No Goalscorer' , make anytime scorer price equal to first scorer price
			if ($current_scorer['scorer'] == 'No Goalscorer') {
				$current_scorer['anytime_scorer_price'] = $current_scorer['first_scorer_price'];
			}
			// if we're finding the anytime price for 'Own, Goal' set it to '11/2'
			if ($current_scorer['scorer'] == 'Own, Goal') {
				$current_scorer['anytime_scorer_price'] = '11/2';
			}
			$all_scorers[$i] = $current_scorer;
		}
		
		// build html table containing the anytime prices and scorers
		// first, calculate how many rows the table will have
		$total_table_rows = ceil((count($all_scorers)) / 2.0);
		// work out whether there's an even or odd number of scorers
		$even_no_of_scorers = 1; // default is yes
		if ((count($all_scorers)) % 2 == 1) {
			$even_no_of_scorers = 0; // set if odd total
		}
		// work out last index of $all_scorers
		$last_scorer_index = count($all_scorers) - 1;
		
		// build two-column table (actually four columns; each column contains a price and name)
		$table_code = '<table style="float:left;">';
		for ($i = 0; $i < $total_table_rows; $i++) {
			$left_hand_scorer = $all_scorers[$i];
			$table_code .= '<tr><td style="float:right;"><b>';
			$table_code .= $left_hand_scorer['anytime_scorer_price'];
			$table_code .= '</b></td><td>';
			$table_code .= $left_hand_scorer['scorer'];
			$table_code .= '</td><td style="float:right">';
			// index of scorer on right is $total_table_rows + $i; if this is equal to or less than $last_scorer_index, build it into
			// table, else build a blank table data section
			if (($total_table_rows + $i) <= $last_scorer_index) {
				$right_hand_scorer = $all_scorers[$total_table_rows + $i];
				$table_code .= '<b>';
				$table_code .= $right_hand_scorer['anytime_scorer_price'];
				$table_code .= '</b></td><td>';
				$table_code .= $right_hand_scorer['scorer'];
			} else {
				$table_code .= '</td><td>';
			}
			$table_code .= '</td></tr>';
			// insert separator line every five lines (after the first seven)
			if (($i - 7) % 5 == 0) {
				$table_code .= '<tr><td>_</td><td></td><td></td><td></td></tr>';
			}
		}
		
		// form a list of all unique prices, for building the table of price conversions to be placed on the right hand side
		// this will aid in typing in the anytime prices on the football coupon
		$all_price_strings = array();
		foreach($all_scorers as $current_scorer) {
			$current_price_string = $current_scorer['first_scorer_price'];
			if (!in_array($current_price_string, $all_price_strings)) {
				$all_price_strings[] = $current_scorer['first_scorer_price'];
			}
		}
		
		// loop through $price_conversions; each time we find a key in $price_conversions that is also in $all_price_strings,
		// create a two item array in the form:
		// {
		//		'first_scorer_price'	=> <first_scorer_price_string>,
		//		'anytime_scorer_price'	=> <anytime_scorer_price_string>
		// }
		// Append this array to the array $small_html_table_price_conversions
		// Looping through $price_conversions (which is already sorted in price order) ensures that all of the prices will be in order
		$small_html_table_price_conversions = array();
		$price_conversion_keys = array_keys($price_conversions);
		foreach ($price_conversion_keys as $current_price_conversion_key) {
			// if we're at 11/2, add it
			if ($current_price_conversion_key == '11/2') {
				$array_to_add = array(
								'first_scorer_price'	=> '11/2',
								'anytime_scorer_price'	=> '13/8'
									);
				$small_html_table_price_conversions[] = $array_to_add;
				continue;
			}
			if (in_array($current_price_conversion_key, $all_price_strings)) {
				$price_array = array(
								'first_scorer_price'	=> $current_price_conversion_key,
								'anytime_scorer_price'	=> $price_conversions[$current_price_conversion_key]
									);
				$small_html_table_price_conversions[] = $price_array;
			}
		}
		
		// test print
		// echo "small_html_table_price_conversions:<br>\n";
		// print_r($small_html_table_price_conversions);
		
		// now that we've got all the price conversions for the small html table, in order of price, build the small html table
		// containing the prices
		// total_rows for the table is going to be 1 + ceil(count($small_html_table_price_conversions) / 2.0)
		$total_rows = 1 + ceil(count($small_html_table_price_conversions) / 2.0);
		$total_prices = count($small_html_table_price_conversions);
		// echo "total_prices = $total_prices<br>\n";
		// echo "total_rows = $total_rows<br>\n";
		$max_price_conversion_index = $total_prices - 1;
		
		// build the table
		$price_table_code = '<table style="float:right;">';
		$price_table_code .= '<tr><td style="float:right;"><b>First</b></td><td><b>';
		$price_table_code .= 'Anytime</b></td><td style="float:right;"><b>First</b></td><td><b>Anytime</b></td></tr>';
		for ($i = 0; $i < $total_rows; $i++) {
			$price_table_code .= '<tr><td style="float:right;"><b>';
			$price_table_code .= $small_html_table_price_conversions[$i]['first_scorer_price'];
			$price_table_code .= '</b><td>';
			$price_table_code .= $small_html_table_price_conversions[$i]['anytime_scorer_price'];
			$price_table_code .= '</td><td style="float:right;"><b>';
			$right_hand_price_index = $i + $total_rows;
			// if ($right_hand_price_index) <= max_price_conversion_index, add the right-hand prices
			if ($right_hand_price_index <= $max_price_conversion_index) {
				$price_table_code .= $small_html_table_price_conversions[$right_hand_price_index]['first_scorer_price'];
				$price_table_code .= '</b></td><td>';
				$price_table_code .= $small_html_table_price_conversions[$right_hand_price_index]['anytime_scorer_price'];
			} else {
				$price_table_code .='</b></td><td></td>';
			}
			$price_table_code .= '</tr>';
		}
		$price_table_code .= '</table>';
		
		// create code joining the two tables
		$two_table_code = $table_code.$price_table_code;
		
		return $two_table_code;
	}
	// ==================================================================================================================
	function multi_scrape_gbgb_dot_org_dog_results_wrapper($multipage_html) {
	
		// explode multipage_html along '<!DOCTYPE HTML'
		$html_for_pages = explode('<!DOCTYPE HTML', $multipage_html);
		
		// remove first element, which doesn't contain html for a dog results page
		array_shift($html_for_pages);
	
		$write_string = '';
	
		// loop through html for results pages, extracting the results from each and building a big write_string to return
		foreach ($html_for_pages as $single_page_html) {
			$write_string .= scrape_gbgb_dot_org_dot_uk_results($single_page_html);
		}
	
		return $write_string;
	}
	// ==================================================================================================================
	// function to scrape dog results from a "Meeting Results" page on www.gbgb.org.uk
	// e.g. http://www.gbgb.org.uk/resultsMeeting.aspx?id=125698
	function scrape_gbgb_dot_org_dot_uk_results($page_code) {
	
		// Extract all the code blocks, place them in $code_blocks
		// $length_of_page_code = strlen($page_code);
		// echo "length_of_page_code = $length_of_page_code<br>\n";
	
	
		// the string '<div class="resultsBlockSeparator">' is at the beginning of all code blocks
		// Simply split $page_code along this string to get all the code blocks
		// Might have to get rid of the first block after this
		$split_sentinel = '<div class="resultsBlockHeader clearfix'; // changed this
		$code_blocks = explode($split_sentinel, $page_code);

		$total_code_blocks = count($code_blocks);
		
		// // test print
		// echo "total_code_blocks = $total_code_blocks<br>\n";

		// remove the first code block, which doesn't contain the information for a race
		array_shift($code_blocks);

		// // test print the code blocks
		// for ($i = 0; $i < $total_code_blocks; $i++) {
			// $current_code_block = $code_blocks[$i];
			// echo "===================================================================================================<br>\n";
			// echo "code_blocks[$i]:<br>\n";
			// echo "$current_code_block<br>\n";
		// }
		
		// ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		// Now loop through the code blocks and extract all the race result and dog details
		// For each race, extract:
		// 1. meeting
		// 2. date
		// 3. time of race
		// 4. forecast payout
		// 5. tricast payout (if present)
		// For each dog in the race result, extract:
		// 1. Finish position
		// 2. Name of dog
		// 3. Trap no
		// 4. SP
		// +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		foreach ($code_blocks as $current_code_block) {
			
			// extract meeting;
			$start = strpos($current_code_block, '<div class="track">') + 19;
			$current_code_block = substr($current_code_block, $start);
			$end = strpos($current_code_block, '|');
			$meeting = substr($current_code_block, 0, $end);
			$meeting = trim($meeting);
			$meeting = str_replace('&nbsp;', '', $meeting);
			$current_code_block = substr($current_code_block, $end);
			// echo "meeting = $meeting<br>\n";
			
			// extract date
			$start = strpos($current_code_block, '<div class="date">') + 18;
			$current_code_block = substr($current_code_block, $start);
			$end = strpos($current_code_block, '</div>');
			$date_string = substr($current_code_block, 0, $end);
			$current_code_block = substr($current_code_block, $end + 6);
			// echo "date_string = $date_string<br>\n";
			
			// extract time of race
			$start = strpos($current_code_block, '<div class="datetime">') + 22;
			$current_code_block = substr($current_code_block, $start);
			$end = strpos($current_code_block, '|');
			$time_string = substr($current_code_block, 0, $end);
			$time_string = trim($time_string);
			$time_string = str_replace('&nbsp;', '', $time_string);
			$current_code_block = substr($current_code_block, $end + 8);
			// echo "time_string = $time_string<br>\n";
			
			// extract forecast payout, if present
			if (!strpos($current_code_block, 'Forecast:') === false) {
				$fc_block_start = strpos($current_code_block, 'Forecast:');
				$fc_code_block = substr($current_code_block, $fc_block_start);
				$pos = strpos($fc_code_block, 'Forecast:');
				// echo "Forecast: found at position $pos<br>\n";
				$fc_code_block = substr($fc_code_block, $pos);
				$close_bracket_pos = strpos($fc_code_block, ")");
				// echo "close_bracket_pos = $close_bracket_pos<br>\n";
				$fc_code_block = substr($fc_code_block, $close_bracket_pos + 2);
				// echo "After chopping to and including close_bracket_pos, fc_code_block is:<br>\n";
				// echo "$fc_code_block<br>\n";
				// move to space after forecast payout, extract forecast payout
				$pos = 0;
				while (substr($fc_code_block, $pos, 1) != ' ') {
					$pos++;
				}
				$forecast_string = substr($fc_code_block, 0, $pos);
				// if there's a '<' in $forecast_string, chop off everything from the '<' onwards, trim the string
				if (!strpos($forecast_string, '<') === false) {
					$angle_bracket_pos = strpos($forecast_string, '<');
					$forecast_string = substr($forecast_string, 0, $angle_bracket_pos);
					$forecast_string = trim($forecast_string);
				}
				// echo "forecast_string = $forecast_string<br>\n";
				$fc_code_block = substr($fc_code_block, $pos);
			} else {
				$forecast_string = ''; // default if no forecast present
			}
			// echo "===============================================================================<br>\n";

			// // extract tricast payout (if present), otherwise tricast string is empty
			if (!strpos($current_code_block, 'Tricast:') === false) {
				$tc_block_start = strpos($current_code_block, 'Tricast:');
				$tc_code_block = substr($current_code_block, $tc_block_start);
				$pos = strpos($tc_code_block, 'Tricast:');
				// echo "Tricast: found at position $pos<br>\n";
				$tc_code_block = substr($tc_code_block, $pos);
				$close_bracket_pos = strpos($tc_code_block, ")");
				// echo "close_bracket_pos = $close_bracket_pos<br>\n";
				$tc_code_block = substr($tc_code_block, $close_bracket_pos + 2);
				// echo "After chopping to and including close_bracket_pos, tc_code_block is:<br>\n";
				// echo "$tc_code_block<br>\n";
				// move to space after tricast payout, extract tricast payout
				$pos = 0;
				while (substr($tc_code_block, $pos, 1) != '<') {
					$pos++;
				}
				$tricast_string = substr($tc_code_block, 0, $pos);
				$tricast_string = trim($tricast_string); // changed
				// echo "tricast_string = $tricast_string<br>\n";
				$fc_code_block = substr($fc_code_block, $pos);
			} else {
				$tricast_string = ''; // default if no forecast present
			}
			// echo "===============================================================================<br>\n";
			
			// Now extract all the code blocks that relate to a single dog
			// Each dog is represented by three code blocks; the three sentinel strings that mark the beginning of each mini
			// code block are:
			// '<ul class="contents line1">', '<ul class="contents line2">' and '<ul class="contents line3">'
			// Each of the mini code blocks ends with a closing '</ul>' tag
			// For each dog, extract all three of these code blocks and join them together into a single code block string
			// Place all of the dog code blocks in an array named $dog_code_blocks
			$dog_code_blocks = array();
			$sentinel_1 = '<ul class="contents line1">';
			$sentinel_2 = '<ul class="contents line2">';
			$sentinel_3 = '<ul class="contents line3">';
			while (!strpos($current_code_block, $sentinel_1) === false) {
				$start = strpos($current_code_block, $sentinel_1);
				$current_code_block = substr($current_code_block, $start);
				$end = strpos($current_code_block, $sentinel_3);
				// now move to closing '</ul>' tag
				while (substr($current_code_block, $end, 5) != "</ul>") {
					$end++;
				}
				$end += 5;
				$current_dog_code_block = substr($current_code_block, 0, $end);
				$dog_code_blocks[] = $current_dog_code_block;
				$current_code_block = substr($current_code_block, $end);
			}
			// echo "=================================================================================================<br>\n";
			// echo "dog_code_blocks:<br>\n";
			// print_r($dog_code_blocks);
		
			// Loop through $dog_code_blocks
			// For each dog in the race result, extract:
			// 1. Finish position
			// 2. Name of dog
			// 3. Trap no
			// 4. SP
			// Form an array for each dog, containing all of the above information, then append the single dog array to a
			// larger array named $all_dog_info
			$all_dog_info = array();
			foreach ($dog_code_blocks as $dog_code_block) {
				// get finish position
				$start = strpos($dog_code_block, '<li class="first essential fin">') + 32;
				// echo "start = $start<br>\n";
				$end = $start + 1;
				while (substr($dog_code_block, $end, 5) != "</li>") {
					$end++;
				}
				// echo "end = $end<br>\n";
				
				$finish_pos = substr($dog_code_block, $start, $end - $start);
				// echo "finish_pos = $finish_pos<br>\n";
				$dog_code_block = substr($dog_code_block, $end);
				// get name of dog
				$start = strpos($dog_code_block, 'dogName=');
				while (substr($dog_code_block, $start, 1) != '>') {
					$start++;
				}
				$start++;
				$end = $start + 1; // changed
				while (substr($dog_code_block, $end, 1) != '<') {
					$end++;
				}
				$dog_name = substr($dog_code_block, $start, $end - $start);
				// echo "dog_name = $dog_name<br>\n";
				
				$dog_code_block = substr($dog_code_block, $end);
				// get trap no
				$start = strpos($dog_code_block, '<li class="trap">') + 17;
				$end = $start + 1;
				while (substr($dog_code_block, $end, 1) != '<') {
					$end++;
				}
				$trap_no = substr($dog_code_block, $start, $end - $start);
				$dog_code_block = substr($dog_code_block, $end);
				// echo "trap_no = $trap_no<br>\n";
				
				// get SP
				$start = strpos($dog_code_block, '<li class="sp">') + 15;
				$dog_code_block = substr($dog_code_block, $start);
				$end = strpos($dog_code_block, "</li>");
				$sp = substr($dog_code_block, 0, $end);
				// echo "sp = $sp<br>\n";
				$dog_code_block = substr($dog_code_block, $end + 5);
				
				
				// build array for this dog
				$current_dog_array = array(
					'finish_pos'		=> $finish_pos,
					'dog_name'			=> $dog_name,
					'trap_no'			=> $trap_no,
					'sp'				=> $sp
											);
				// append this dog array to $all_dog_info
				$all_dog_info[] = $current_dog_array;
			}
			// build a large array containing all the information for the race, append it to $all_race_results
			$current_race_array = array(
								'meeting'		=> $meeting,
								'date'			=> $date_string,
								'time'			=> $time_string,
								'fc'			=> $forecast_string,
								'tc'			=> $tricast_string,
								'dog_info'		=> $all_dog_info
										);
			$all_race_results[] = $current_race_array;
		}
		// test print
		// print_r($all_race_results);
		
		// build a big write string from $all_race_results, return it
		$write_string = '';
		$position_suffixes = array('', 'st', 'nd', 'rd', 'th', 'th', 'th');
		$first_race_result = $all_race_results[0];
		$title_string = 'Race results for ';
		$title_string .= $first_race_result['meeting'];
		$title_string .= ' (';
		$title_string .= $first_race_result['date'];
		$title_string .= ')';
		
		// build title dashes
		$length_of_title_string = strlen($title_string);
		$title_dash_string = '';
		for ($i = 0; $i < $length_of_title_string; $i++) {
			$title_dash_string .= '=';
		}
		$title_dash_string .= '<br>';
		$title_string = $title_dash_string.$title_string.'<br>'.$title_dash_string;
		
		$write_string .= $title_string.'<br>';
		foreach ($all_race_results as $current_race_result) {
			// race time + meeting e.g. '19:25 Sunderland'
			$write_string .= $current_race_result['time'];
			$write_string .= ' ';
			$write_string .= $current_race_result['meeting'];
			$write_string .= '<br>';
			
			
			$dogs_info = $current_race_result['dog_info'];
			foreach ($dogs_info as $current_dog_info) {
				// finish pos
				$finish_pos = $current_dog_info['finish_pos'];
				$write_string .= $finish_pos;
				$write_string .= $position_suffixes[$finish_pos];
				$write_string .= ' ';
				// trap no
				$write_string .= 'T';
				$write_string .= $current_dog_info['trap_no'];
				$write_string .= ' ';
				// dog name
				$write_string .= $current_dog_info['dog_name'];
				$write_string .= ' ';
				// price
				$write_string .= '(';
				$write_string .= $current_dog_info['sp'];
				$write_string .= ')';
				$write_string .= '<br>';
			}
			
			// if present, add forecast and tricast payouts
			if ($current_race_result['fc'] != '') {
				$write_string .= 'F/C: ';
				$write_string .= $current_race_result['fc'];
			}
			if ($current_race_result['tc'] != '') {
				$write_string .= ' ';
				$write_string .= 'T/C: ';
				$write_string .= $current_race_result['tc'];
			}
			$write_string .= '<br><br>';
			
		}
		
		return $write_string;
	}
	// =================================================================================================================
	// extract the 3 balls from an oddschecker page, build 3 ball strings for pasting into McLeans system
	function oddschecker_extract_3_balls($page_code) {
		
		$write_string = '';
		
		// each code block containing a 3 balls begins with 'data-market-id="'
		$sentinel = '<td class="time"';
		
		$code_blocks = explode($sentinel, $page_code);
		array_shift($code_blocks);
		$total_code_blocks = count($code_blocks);
		
		// each code block ends with '</tr>
		// truncate the final code block to this end_sentinel
		$final_code_block = $code_blocks[$total_code_blocks - 1];
		$end_loc = strpos($final_code_block, '</tr>');
		$final_code_block = substr($final_code_block, 0, $end_loc);
		$code_blocks[$total_code_blocks - 1] = $final_code_block;
		
		// echo "total_code_blocks = $total_code_blocks";
		// print_r($code_blocks);
		
		// Now that we've got all the code blocks, we're going to loop through them and extract:
		// 1. the time
		// 2. the participant names
		// Then we're going to form a string in the format <TIME> <NAME1>/<NAME2>/<NAME3><br>
		// e.g. 15:30 M.Davis/F.Freud/S.Samuels<br>
		// This string will then be appended to $write_string
		foreach ($code_blocks as $code_block) {
			
			// get the time; sentinel string is '<p>'
			$time_start_pos = strpos($code_block, '<p>') + 3;
			$code_block = substr($code_block, $time_start_pos);
			$time_end_pos = strpos($code_block, '</p>');
			$time_string = substr($code_block, 0, $time_end_pos);
			// echo "time_string = $time_string<br>\n";
			
			// get the three names and reform them
			$names = array();
			while (!strpos($code_block, 'data-name="') === false) {
				$name_start_pos = strpos($code_block, 'data-name="') + 11;
				$code_block = substr($code_block, $name_start_pos);
				$name_end_pos = strpos($code_block, '"');
				$current_name = substr($code_block, 0, $name_end_pos);
				$names[] = $current_name;
				$code_block = substr($code_block, $name_end_pos + 1);
			}
			// print_r($names);
			// for each participant name, we're going to use add_comma_to_name(name)
			// then we're going to see if the comma is in position zero of the reformed name
			// if it isn't, we're going to get the substring before the comma, trim it, explode it along spaces,
			// then get all the first names and extract the initials, then join the initials with dots and prepend
			// them to the bit of the name after the comma
			// So, for example, James Herbie,Brennan will become J H Brennan
			$reformed_names = array();
			foreach ($names as $current_name) {
				$comma_name = add_comma_to_name($current_name);
				$comma_pos = strpos($comma_name, ',');
				// if the comma is not in position zero, get the initials, reform the name
				if (!$comma_pos == 0) {
					$name_parts = explode(',', $comma_name);
					// print_r($name_parts);
					$first_bit_of_name = $name_parts[0];
					$last_bit_of_name = $name_parts[1];
					// get the initials
					$first_names = explode(' ', $first_bit_of_name);
					$initials = array();
					foreach ($first_names as $first_name) {
						$first_name = trim($first_name);
						$current_initial = substr($first_name, 0, 1);
						$initials[] = $current_initial;
					}
					$all_initials_joined = implode('.', $initials);
					$reformed_name = $all_initials_joined.".".$last_bit_of_name;
				} else {
					$reformed_name = $comma_name;
				}
				$reformed_names[] = $reformed_name;
			}
			// form a line string, append it to $write_string
			$line_string = $time_string."<br>";
			$line_string .= implode('/', $reformed_names);
			$line_string .= "<br><br>";
			$write_string .= $line_string;
		}
		return $write_string;
	}
	// =================================================================================================================
	// extract participant names from an oddschecker webpage paste
	// 13/2/15 - this function has been reimplemented as a "download the webpage and extract the participants" function
	// Extracting the participants from oddschecker text pastes with the participants in different positions was
	// proving too problematic, with too many corner cases - extracting them from the html for a oddschecker webpage
	// is much simpler
	function oddschecker_extract_names_from_text_paste($target_url, $add_comma) {
		
		$write_string = '';
		
		$page_code = download_webpage($target_url, 'oddschecker_webpage.htm', 1);
		
		$page_code_length = strlen($page_code);
		// echo "page_code_length = $page_code_length<br>\n";
		
		//
		// sentinel string for the beginning of a participant name is 'data-name='
		$sentinel = 'data-name=';
		$participants = array(); // append participant names to this array
		while (!strpos($page_code, $sentinel) === false) {
		// for ($i = 0; $i < 10; $++) { // replace this with uncommented line above once bug has been found
			$pos = strpos($page_code, $sentinel);
			// echo "pos = $pos<br>\n";
			while (substr($page_code, $pos, 1) != '"') {
				$pos++;
			}
			$start = $pos;
			// echo "start = $start<br>\n";
			$pos = ++$start;
			while (substr($page_code, $pos, 1) != '"') {
				$pos++;
			}
			$end = $pos;
			$current_participant = substr($page_code, $start, $end - $start);
			if (!in_array($current_participant, $participants)) {
				$participants[] = $current_participant;
			}
			$page_code = substr($page_code, $end + 1);
		}
		
		// print_r($participants);
		
		# if $add_comma == 1, loop through the names and add commas to them
		if ($add_comma == 1) {
			$total_participants = count($participants);
			for ($i = 0; $i < $total_participants; $i++) {
				$current_participant = $participants[$i]; // take participant out of array
				$current_participant = add_comma_to_name($current_participant);
				$participants[$i] = $current_participant; // put participant back in array
			}
		}
		
		# join all the participants, return text
		$join_string = "<br>\n";
		$output_text = implode($join_string, $participants);
		
		return $output_text;
	}
	// =================================================================================================================
	// 	// split text along newlines
	// 	$lines = explode("\n", $page_text);
		
	// 	# get the sentinel string that the user has entered on the first line; this is the name of the first participant
	// 	# to be extracted from the page; it will also enable us to determine which element number contains the participants
	// 	# that we wish to extract
	// 	$sentinel_participant = $lines[0];
	// 	$sentinel_participant = trim($sentinel_participant);
		
	// 	# remove the first line from $lines (it merely contains the user-entered sentinel string)
	// 	array_shift($lines);
		
	// 	# shift lines off the front of the array until we find the first line that contains the sentinel string
	// 	while (strpos($lines[0], $sentinel_participant) === false) {
	// 		array_shift($lines);
	// 	}
		
	// 	# echo "out of shifting lines...";
		
	// 	# now $lines begins with all the lines that contain participants
	// 	# find out how many tabs are in the first line - this is the number of tabs in each participant line
	// 	$target_tab_number = substr_count($lines[0], "\t");
	// 	echo "target_tag_number = $target_tab_number";
		
	// 	# extract all the lines that contain the same number of tabs as $lines[0]
	// 	$kept_lines = array();
	// 	foreach ($lines as $current_line) {
	// 		if (substr_count($current_line, "\t") == $target_tab_number) {
	// 			$kept_lines[] = $current_line;
	// 		}
	// 	}
	// 	$lines = $kept_lines;
		
	// 	# test print
	// 	print_r($kept_lines);
		
	// 	# loop through $kept_lines
	// 	$formatted_lines = array();
	// 	foreach ($kept_lines as $line) {
	// 		$line = trim($line);
	// 		# get rid of "Show Graph"
	// 		$pos = strpos($line, "Show Graph") + 10;
	// 		$line = substr($line, $pos);
	// 		# echo "==========================================================================<br>\n";
	// 		# echo "After getting rid of Show Graph, line is:<br>\n$line<br>\n";
	// 		# $line = str_replace('\n', "", $line);
	// 		# if there's a tab in the line, find location of first tab, chop it off and everything following it, then
	// 		# trim the line, otherwise just trim the line
	// 		if (!strpos($line, "\t") === false) {
	// 			$tab_pos = strpos($line, "\t");
	// 			# echo "tab_pos is $tab_pos<br>\n";
	// 			$line = substr($line, 0, $tab_pos);
	// 		}
	// 		$line = trim($line);
	// 		# echo "After removing first tab and everything after it, line is:<br>\n$line<br>\n";
	// 		# if $add_comma == 1, pass $line to the *** function to replace the final space with a comma
	// 		# echo "Before add_comma_to_name, line is:<br>\n$line<br>\n";
	// 		if ($add_comma == 1) {
	// 			$line = add_comma_to_name($line);
	// 			# echo "After add_comma_to_name, line is:<br>\n$line<br>\n";
	// 		}
	// 		$line = trim($line);
	// 		$formatted_lines[] = $line;
	// 	}
	// 	// join all kept lines, return text
	// 	$join_string = "<br>\n";
	// 	$output_text = implode($join_string, $formatted_lines);
		
	// 	return $output_text;
	// }
	// =================================================================================================================
	// FINISHED (3/8/15) - extracts Hills goalscorer names AND prices
	function extract_hills_scorer_names_and_prices($hills_page_text) {
	  
	  $endl = "<br>\n";
	  
		$page_text = ""; // build page text here
		
		while (!strpos($hills_page_text, "No Scorers") === false) {
		  // find "Goalscorer Markets", chop off everything before it
		  $start = strpos($hills_page_text, "Goalscorer Markets") + 18;
		  $end = strpos($hills_page_text, "No Scorers");
		  $scorer_text_length = $end - $start;
		  $scorer_text = substr($hills_page_text, $start, $scorer_text_length);
		  $page_text = $page_text.$scorer_text;
		  $hills_page_text = substr($hills_page_text, $end + 10);
		}
	
		// split text along newlines
		$lines = explode("\n", $page_text);
		$output_text = "<table>"; // empty string to build list of team names, player names and price strings
		$current_team = 1;
		
		// loop along lines
		// foreach($lines as $line) {
		$total_lines = count($lines);
		for ($i = 0; $i < $total_lines; $i++) {
		  $line = $lines[$i];
		  if ($i < $total_lines - 1) {
			$next_line = $lines[$i + 1];
		  } else {
			$next_line = "";
		  }
		  // There are seven tabs in lines that contain a team name. Whenever we find such a line:
		  // 1. Extract the team name, strip exterior whitespace and rebuild it as "<tr><td>TEAM NAME</td><td></td></tr>\n"
		  // 2. Append team name to $output_text
		  $tab_count = substr_count($line, "\t");
		  // echo "tab_count = $tab_count".$endl;
		  if ($tab_count == 7) {
			$first_tab_loc = strpos($line, "\t");						// find first tab
			$team_name = substr($line, 0, $first_tab_loc);				// chop off everything from the first tab onwards
			$team_name = trim($team_name);								// trim the string
			$team_name = "<tr><td><strong>".$team_name."</strong></td><td></td></tr>";	// reformat as html table row
			$output_text = $output_text.$team_name;						// append team name to $output_text
			$current_team = $current_team + 1;							// increment current team
		  } else {
			// Trim the line - if it doesn't contain a slash and it isn't a blank string,
			// and it's not "EVS", it's a name
			// If a name, extract the price string on the next line, then rebuild the player name and price string in a single
			// html table row, then append it to $output_text
			$line = trim($line);
			if (((strpos($line, "/") === false) and (strlen($line) > 0)) and $line != "EVS") {
			  // line contains a name:
			  // 1. extract name
			  // 2. extract price string from next line
			  // 3. Rebuild name and price in a single html table row and append it to $output_text
			  if (strpos($line, " ") >= 1) {
				$loc = strlen($line) - 1;
				while ($line{$loc} != " ") {
				  $loc = $loc - 1;
				}
				$first_bit_of_name = substr($line, 0, $loc);
				$last_bit_of_name = substr($line, $loc + 1);
				$rebuilt_name = $first_bit_of_name." ".$last_bit_of_name;
				$price_string = trim($next_line);
				$this_table_row = "<tr><td>".$rebuilt_name."</td><td";
				$this_table_row .= ' style="text-align: right"';
				$this_table_row .= "><strong>".$price_string."</strong></td></tr>";
				$output_text = $output_text.$this_table_row;
			  } else { // no space, rebuild name as comma + $line, append to $output_text
				$rebuilt_name = $line;
				$price_string = trim($next_line);
				$this_table_row = "<tr><td>".$rebuilt_name."</td><td style=".'"text-align:right"';
				$this_table_row .= "><strong>".$price_string."</strong></td></tr>";
				$output_text = $output_text.$this_table_row;
			  }
			}
		  }
		}
		// add final bit of table
		$output_text .= "</table>";
		
		return $output_text;
	}
	// =================================================================================================================
	function extract_hills_scorer_names($hills_page_text) {
	  
	  $endl = "<br>\n";
	  
	  $current_team_name = ''; // store current team name here; used for building wincast strings
	  
	  $wincast_code_block = ''; // build code block containing all wincast pastable strings here
	  
		$page_text = ""; // build page text here
		
		while (!strpos($hills_page_text, "No Scorers") === false) {
		  // find "Goalscorer Markets", chop off everything before it
		  $start = strpos($hills_page_text, "Goalscorer Markets") + 18;
		  $end = strpos($hills_page_text, "No Scorers");
		  $scorer_text_length = $end - $start;
		  $scorer_text = substr($hills_page_text, $start, $scorer_text_length);
		  $page_text = $page_text.$scorer_text;
		  $hills_page_text = substr($hills_page_text, $end + 10);
		}
	
		// split text along newlines
		$lines = explode("\n", $page_text);
		$output_text = "<table>"; // empty string to build list of team names, player names and price strings
		$current_team = 1;
		
		// loop along lines
		// foreach($lines as $line) {
		$total_lines = count($lines);
		for ($i = 0; $i < $total_lines; $i++) {
		  $line = $lines[$i];
		  if ($i < $total_lines - 1) {
			$next_line = $lines[$i + 1];
		  } else {
			$next_line = "";
		  }
		  // There are seven tabs in lines that contain a team name. Whenever we find such a line:
		  // 1. Extract the team name, strip exterior whitespace and rebuild it as "<tr><td>TEAM NAME</td></tr>\n"
		  // 2. Append team name to $output_text
		  // 3. Build a html line for the wincast strings code block and append it to $wincast_code_block
		  $tab_count = substr_count($line, "\t");
		  // echo "tab_count = $tab_count".$endl;
		  if ($tab_count == 7) {
			$first_tab_loc = strpos($line, "\t");						// find first tab
			$team_name = substr($line, 0, $first_tab_loc);				// chop off everything from the first tab onwards
			$team_name = trim($team_name);								// trim the string
			$current_team_name = $team_name;	// set $current_team_name for building wincast strings
			$current_team_name = reformat_wincast_name($current_team_name);
			$team_name = "<tr><td><strong>".$team_name."</strong></td></tr>";	// reformat as html table row
			$output_text = $output_text.$team_name;						// append team name to $output_text
			$current_team = $current_team + 1;							// increment current team
			// add team name to $wincast_code_block
			$wincast_code_block .= "<strong>".$current_team_name." Wincast Strings</strong><br>\n";
		  } else {
			// Trim the line - if it doesn't contain a slash and it isn't a blank string,
			// and it's not "EVS", it's a name
			// If a name, extract the price string on the next line, then rebuild the player name and price string in a single
			// html table row, then append it to $output_text
			// Also: rebuild the name, form a wincast string from the name and the current team and append it to $wincast_code_block
			$line = trim($line);
			if (((strpos($line, "/") === false) and (strlen($line) > 0)) and $line != "EVS") {
			  // line contains a name:
			  // 1. extract name
			  // 2. extract price string from next line
			  // 3. Rebuild name and price in a single html table row and append it to $output_text
			  // 4. rebuild name as initials plus surname, build a wincast string and append it to $wincast_code_block
			  $proto_wincast_name = $line;
			  if (strpos($line, " ") >= 1) {
				$loc = strlen($line) - 1;
				while ($line{$loc} != " ") {
				  $loc = $loc - 1;
				}
				$first_bit_of_name = substr($line, 0, $loc);
				$last_bit_of_name = substr($line, $loc + 1);
				$rebuilt_name = $first_bit_of_name.",".$last_bit_of_name;
				$price_string = trim($next_line);
				$this_table_row = "<tr><td>".$rebuilt_name."</td></tr>";
				$output_text = $output_text.$this_table_row."\n";
			  } else { // no space, rebuild name as comma + $line, append to $output_text
				$rebuilt_name = ",".$line;
				$this_table_row = "<tr><td>".$rebuilt_name."</td></tr>\n";
				$output_text = $output_text.$this_table_row;
			  }
			  $wincast_name = reformat_wincast_name($proto_wincast_name);
			  $current_wincast_html = $wincast_name."/".$current_team_name."<br>";
			  $wincast_code_block .= $current_wincast_html;
			}
		  }
		}
		// add final bit of table
		$output_text .= "</table>\n<br>\n".$wincast_code_block;
		
		return $output_text;
	}
	// =================================================================================================================
	function extract_hills_tennis_names($text)
	{
		// split text along newlines
		$lines = explode("\n", $text);
		
		// get length of $lines array
		$lines_length = count($lines);
		  
		// iterate along lines - if a lines contains "   v   ", append it to the array $kept_lines
		$kept_lines = array();
		foreach($lines as $line) {
			if (!strpos($line, "   v   ") === false) {
				$line = trim($line);   // remove exterior whitespace
				$kept_lines[] = $line; // add line to $kept_lines array
			}
		}
  
		$names_and_teams = array();
		
		// loop through $kept_lines
		foreach($kept_lines as $line) {
			$v_pos = strpos($line, "   v   "); // find "   v   " location
			// get name on left, remove whitespace, append it to $names_and_teams
			$name_on_left = substr($line, 0, $v_pos);
			$name_on_left = trim($name_on_left);
			$names_and_teams[] = $name_on_left;
			// get name on right, remove whitespace, append it to $names_and_teams
			$name_on_right = substr($line, $v_pos + 7);
			$name_on_right = trim($name_on_right);
			$names_and_teams[] = $name_on_right;
		}
		
		// create two new arrays named $names and $teams
		// loop through $names_and_teams:
		// if an element contains a "/", append it to $teams, otherwise append it to $names
		$names = array();
		$teams = array();
		foreach($names_and_teams as $element) {
			if (strpos($element, "/") === false) { // not a doubles team, so append it to $names
				$names[] = $element;
			} else {
				$teams[] = $element;
			}
		}

		// loop through $teams, split each element along the '/' and append the two names to $names
		foreach($teams as $team) {
			$slash_loc = strpos($team, "/");			// find location of slash dividing the two names
			$left_name = substr($team, 0, $slash_loc);  // get the name on left
			$left_name = trim($left_name);				// remove exterior whitespace
			$names[] = $left_name;						// append to $names array

			$right_name = substr($team, $slash_loc + 1);
			$right_name = trim($right_name);
			$names[] = $right_name;
		}

		// loop through $names
		$names_with_commas = array(); // array to add reformated names with commas to
		foreach($names as $name) {

			// trim each element
			$name = trim($name);
			// if no space in name, prepend a comma, append reformatted name to $names_with_commas,
			// otherwise rebuild the name, replacing the final space with a comma before adding it
			// to $names_with_commas
			if (strpos($name, " ") == false) {
				$name = ",".$name;				// prepend commas
				$names_with_commas[] = $name;	// add to array
			} else {
				// find final space location
				$name_length = strlen($name);
				$pos = $name_length - 1;
				while ($name{$pos} != ' ') {
					$pos = $pos - 1;
				}
				// find first bit of name
				$first_bit_of_name = substr($name, 0, $pos);
				// find last bit of name
				$last_bit_of_name = substr($name, $pos + 1);
				// rebuild name
				$rebuilt_name = $first_bit_of_name.",".$last_bit_of_name;
				// add name to array
				$names_with_commas[] = $rebuilt_name;
			}
		}

		// construct $write_string from $names_with_commas
		$write_string = "==== Player Names ====\n<br>";
		foreach($names_with_commas as $name) {
			$write_string = $write_string.$name;
			$write_string = $write_string."\n<br>\n";
		}
		
		// write code here to reformat doubles team names:
		// 1. split the names into first and second
		// 2. send each name to a function which extracts the player's last name, extracts the initial of each of
		//    his/her first names and rebuilds the name as the initials followed by the surname
		// 3. rebuild the doubles team name as the two rebuilt names with a slash in between
		
		$rebuilt_teams = array();
		foreach($teams as $team) {
			$slash_pos = strpos($team, "/");
			$name1 = substr($team, 0, $slash_pos);
			$name2 = substr($team, $slash_pos + 1);
			// reformat each name
			$name1 = reformat_tennis_doubles_name($name1);
			$name2 = reformat_tennis_doubles_name($name2);
			// put the doubles team name back together, append to $rebuilt_teams
			$new_team_name = $name1."/".$name2;
			$rebuilt_teams[] = $new_team_name;
		}
		
		// add team names to $write_string
		$write_string = $write_string."\n<br>==== Doubles Teams ====\n<br>";
		foreach ($rebuilt_teams as $team) {
			$team = trim($team);
			$write_string = $write_string.$team;
			$write_string = $write_string."\n<br>";
		}
		
		return $write_string;
	}
	// =================================================================================================================
	// helper function for extract_hills_tennis_names
	function reformat_tennis_doubles_name($name) {
		
		$name = trim($name);
		
		if (strpos($name, " ") === false) { // no space in name - return the same name
			return $name;
		} else { // space in name - let's rebuild it
			$loc = strlen($name) - 1;
			while ($name{$loc} != " ") {
				$loc = $loc - 1;
			}
			$surname = substr($name, $loc + 1);
			$first_names = substr($name, 0, $loc);
			$initials = array();
			// if no spaces in $first_names, append single initial to $initials
			if (strpos($first_names, " ") === false) {
				// get single initial, append it to $initials
				$sole_initial = $first_names{0};
				$initials[] = $sole_initial;
			} else { // at least one space found in name
				// split first names along spaces
				$first_names = explode(" ", $first_names);
				// loop through $first_names array, extracting initials from each name, before appending
				// them to $initials
				foreach($first_names as $this_name) {
					$initials[] = $this_name{0};
				}
			}
			// loop through $first_names, extracting first letter from each one, appending it to an array
			// named $initials
			$rebuilt_name = "";
			foreach ($initials as $initial) {
				$rebuilt_name = $rebuilt_name.$initial." ";
			}
			$rebuilt_name = $rebuilt_name.$surname;
			// echo "$rebuilt_name<br>\n";
			return $rebuilt_name;
		}
	}
	// =================================================================================================================
	// function to reformat player names as initial plus surname; used for building wincast strings
	function reformat_wincast_name($name) {
		
		$name = trim($name);
		
		if (strpos($name, " ") === false) { // no space in name - return the same name
			return $name;
		} else { // space in name - let's rebuild it
			$loc = strlen($name) - 1;
			while ($name{$loc} != " ") {
				$loc = $loc - 1;
			}
			$surname = substr($name, $loc + 1);
			$first_names = substr($name, 0, $loc);
			$initials = array();
			// if no spaces in $first_names, append single initial to $initials
			if (strpos($first_names, " ") === false) {
				// get single initial, append it to $initials
				$sole_initial = $first_names{0};
				$initials[] = $sole_initial;
			} else { // at least one space found in name
				// split first names along spaces
				$first_names = explode(" ", $first_names);
				// loop through $first_names array, extracting initials from each name, before appending
				// them to $initials
				foreach($first_names as $this_name) {
					$initials[] = $this_name{0};
				}
			}
			// loop through $first_names, extracting first letter from each one, appending it to an array
			// named $initials
			$rebuilt_name = "";
			foreach ($initials as $initial) {
				$rebuilt_name = $rebuilt_name.$initial.".";
			}
			$rebuilt_name = $rebuilt_name.$surname;
			// echo "$rebuilt_name<br>\n";
			return $rebuilt_name;
		}
	}
	// =================================================================================================================
	function extract_turf_tv_american_meeting_names($text)
	{
		// split text along newlines
		$lines = explode("\n", $text);

		// lines containing horse names have 4 tabs - loop through lines and collect such lines
		$horse_lines = array();
		foreach($lines as $line) {
			if (substr_count($line, "\t") >= 4) {
				$horse_lines[] = $line;
			}
		}
		
		// loop through lines again - for each line:
		// 1. trim it to remove the tab preceding the name
		// 2. find the first tab remaining in the string (it's directly after the horse name)
		// 3. use the first tab location to chop out the name
		// 4. trim the name, append it to $horse_names
		$horse_names = array();
		foreach ($horse_lines as $line) {
			$line = trim($line);
			$tab_loc = strpos($line, "\t");
			$name = substr($line, 0, $tab_loc);
			$name = trim($name);
			$horse_names[] = $name;
		}
		
		// construct webpage write code
		$write_text = "";
		foreach ($horse_names as $name) {
			if ($name != "Cloth") {
				$write_text = $write_text."$name<br>\n";
			}
		}
		
		return $write_text;
	}
	// =================================================================================================================
	function create_table_from_hills_scorer_names($write_text)
	{
		// split text along "===="
		$teams = explode("\n====", $write_text);
		
		// restore missing "====" from start of all $teams elements except the first
		$rebuilt_teams = array();
		$rebuilt_teams[] = $teams{0};
		$total_remaining_teams = count($teams) - 1;
		for ($n = 1; $n <= $total_remaining_teams; $n = $n + 1) {
			$teams{$n} = "====".$teams{$n};
		}
		
		// build table
		$total_columns = 8;
		$start = "\n<table>\n";
		$end = "\n</table>\n";
		$middle = "";
		// build middle of table
		$team_count = count($teams);
		for($n = 0; $n < $team_count; $n = $n + 1) {
			if (($n + 1) % $total_columns == 1) { // if first column, write start of row table code
				$middle = $middle."\n<tr>\n";
			}
			$middle = $middle."\n<td style=\"vertical-align:top\">\n".$teams{$n}."\n</td>\n";
			if (($n + 1) % $total_columns == 0) { // if final column, write end of row table code
				$middle = $middle."\n</tr>\n";
			}
		}
		
		// construct table
		$table_code = $start.$middle.$end;
		
		return $table_code; // CHANGE THIS WHEN FUNCTION IS FINISHED
	}
	// =================================================================================================================
	// extract bbc football fixtures from text copied and pasted from the bbc football fixtures page
	function extract_bbc_football_fixtures($text)
	{
	
		// build a csv write string by looping through all the lines
		// extract information from three different types of line:
		// date lines, which contain a day
		// match lines, which contain the string " V "
		// league name lines, which begin with a tab and don't contain a colon
		
		$csv_string = ""; // this is the string we're going to build, then write to a csv file at the end
		
		// split $text along newlines
		$original_lines = explode("\n", $text);
		$total_lines = count($original_lines);
		$current_line_no = 0;
		while ($current_line_no <= ($total_lines - 1)) {
			$current_line = $original_lines{$current_line_no};
			// if line contains a day, process it as a day
			if (is_bbc_football_day_line($current_line)) {
				$csv_string = $csv_string.extract_bbc_football_day($current_line);
				$current_line_no = $current_line_no + 1;
				continue;
			}
			// if line contains a match, process it as a match
			$is_football_match = is_bbc_football_match_line($current_line);
			if ($is_football_match) {
				$next_line = $original_lines{$current_line_no + 1};
				// use the third parameter as a flag to indicate to the function what type of match this is,
				// either 1. a regular football match or 2. a postponed match
				$csv_string = $csv_string.extract_bbc_football_match($current_line, $next_line, $is_football_match);
				$current_line_no = $current_line_no + 2; // skip next line, which contains a time string
				continue;
			}
			// if line contains a league name, process it as league title
			if (is_bbc_football_league_line($current_line)) {
				$csv_string = $csv_string.extract_bbc_football_league_name($current_line);
				$current_line_no = $current_line_no + 1;
				continue;
			}
			// if we've fallen through, the line doesn't contain any information we want to scrape, go to the next line
			$current_line_no = $current_line_no + 1;
		}
		return $csv_string;
	}
	// =================================================================================================================
	// helper function for extract_bbc_football_fixtures($text)
	// determines whether a line contains a date by detecting a day substring, if present
	function is_bbc_football_day_line($current_line) {
	
		// prepend a space so that if the function finds a day substring, it isn't found at position zero
		$current_line = " ".$current_line;
		
		$days = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");
		// log_write("Current line:\r\n$current_line");
		foreach ($days as $day) {
			// log_write("Checking current line for $day");
			$pos = strpos($current_line, $day);
			if (!$pos === false) {
				// log_write("$day found!");
				return 1; // day found
			}
		}
		return 0; // no day found
	}
	// =================================================================================================================
	// helper function for extract_bbc_football_fixtures($text)
	// determines whether a line contains a football match fixture by searching for the substring " V "
	function is_bbc_football_match_line($current_line)
	{
		$pos = strpos($current_line, " V ");
		$postponed_pos = strpos($current_line, "P-P");
		if (!$pos === false) {
			return 1; // match found
		}
		if (!$postponed_pos === false) {
			return 2; // postponed match found
		}
		// if we've fallen through, a match hasn't been found
		return 0;
	}
	// =================================================================================================================
	// helper function for extract_bbc_football($text)
	// determines whether a line contains a league name
	// league names begin with a colon and do not contain a colon
	function is_bbc_football_league_line($current_line)
	{
		$first_char = $current_line{0};
		$pos = strpos($current_line, ":");
		$full_time_pos = strpos($current_line, "Full time"); // test for "Full time" at start of line
		$not_an_empty_line = strcmp(trim($current_line), ""); // test for blank line
		if ((($first_char == "\t") && ($pos === false))
				&&
				(($full_time_pos === false) && $not_an_empty_line)) { // it's a league title line
			return 1;
		} else {
			return 0; // not a league line
		}
	}
	// =================================================================================================================
	// extract the date from a bbc football fixture date line
	function extract_bbc_football_day($current_line)
	{
		$current_line = trim($current_line);
		// remove year from end of string
		// if:
		// 1. there's a space in the string, and
		// 2. the position of the final space is 4 away from the end of the string
		// chop off the year
		// e.g. if we have a string "blah 2014"
		// the final space is at location 4 and the length of the string is 9
		// so (length of string - final space location) must be equal to 5
		$space_loc = strpos($current_line, " ");
		log_write("Line:\r\n$current_line\r\nspace_loc = $space_loc\r\n");
		// locate final space
		$pos = strlen($current_line) - 1;
		log_write("pos = $pos\r\n");
		while ($current_line{$pos} != ' ') {
			$pos = $pos - 1;
		}
		log_write("Final space found at position $pos\r\n");
		// chop off year
		$current_line = substr($current_line, 0, $pos);
		log_write("After removing year, line is now:\r\n$current_line\r\n");
		log_write("======================================================================\r\n");
		
		return ",,,".$current_line."\n";
	}
	// =================================================================================================================
	// extract home team, away team, kick off time, build a csv line from them, ending in a newline, return it
	// $type_of_match is a flag indicating the type of match
	// if $type_of_match = 1, it's a regular football match
	// if $type_of_match = 2, it's a postponed match
	function extract_bbc_football_match($current_line, $next_line, $type_of_match)
	{
		if ($type_of_match == 1) { // regular match
			$elements = explode(" V ", $current_line);
		}
		if ($type_of_match == 2) { // postponed match
			$elements = explode("P-P", $current_line);
		}
		$home_team = trim($elements{0});
		$away_team = trim($elements{1});
	
		// sometimes the $time_string is followed by some guff
		// to get rid of the guff, locate the colon, then chop off everything after colon_location + 2,
		// then trim the string
		if ($type_of_match == 1) { // regular match
			$time_string = trim($next_line);
			$colon_location = strpos($time_string, ":");
			$time_string = substr($time_string, 0, $colon_location + 3);
			$time_string = trim($time_string);
		}
		if ($type_of_match == 2) {// postponed match
			$time_string = "P-P";
		}
		
		// build csv line
		$csv_line = ",,".$home_team.",,".$away_team.",,".$time_string."\n";
		
		return $csv_line;
	}
	// =================================================================================================================
	function extract_bbc_football_league_name($current_line)
	{
		// return the trimmed line plus a newline at end
		return ",,,".trim($current_line)."\n";
	}
	// =================================================================================================================
	function send_file_to_user($filename)
	{
		//http_send_file($filename);
		// readfile($filename);
		
		$file = $filename;

		if (file_exists($file)) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename='.basename($file));
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($file));
			ob_clean();
			flush();
			readfile($file);
			exit;
		}
		
		return;
	}
	// =================================================================================================================
	function scrape_oddschecker_fixtures($page_code)
	{
		log_write("Just entered scrape_oddschecker_fixtures()");
		// scrape_oddschecker_bumper_coupon()
		// notes:
		// 1. days begin with '<td class="day"', end with "</p>"
		// 2. league names begins with '<td class="name"', end with "</p>"
		// 3. code blocks containing matches start with '<tr data-market-id="' and end with "</tr>"
		
		// get html paste either from input box or by downloading the target page, call it $page_code
		
		$csv_string = ""; // append to this to build the csv file
		
		// test for one of the three above elements remaining in the page
		// (i.e. a league title, a date or a match code block)
		// $element_remaining_in_page = test_for_element_in_page($page_code);
		// $array_to_return = array($loc, $element_type);
		$loc_and_element_type_array = find_next_element_type($page_code);
		$next_element_pos = $loc_and_element_type_array[0];
		
		// while ($next_element_pos != -1) { // while there's another element to extract
		for ($count = 0; $count < 100; $count++) { // change this once the bug has been found!!!
			
			// find out whether the next element is (1) a date, (2) a league name, or (3) a match,
			$next_element_type_array = find_next_element_type($page_code);
			// extract position and type variables
			$next_element_pos = $next_element_type_array[0];
			$next_element_type = $next_element_type_array[1];
			
			// extract next element, add it to csv string
			if ($next_element_type == 1) { // extract a date
				$returned_array = extract_oddschecker_date($page_code, $next_element_pos);
				$date_string = $returned_array[0];
				log_write($date_string);
				$page_code = $returned_array[1];
				// append date line to csv string
				$csv_string = $csv_string.$date_string."\n";
				log_write("csv_string:\n$csv_string");
			}
			
			if ($next_element_type == 2) { // extract a league name
				$returned_array = extract_oddschecker_league_name($page_code, $element_pos);
				$league_name = $returned_array[0];
				log_write($league_name);
				$page_code = $returned_array[1];
				// append league name to csv string
				$csv_string = $csv_string.$league_name."\n";
				log_write("csv_string:\n$csv_string");
				log_write("+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++");
			}
			
			if ($next_element_type == 3) { // extract a match
				$returned_array = extract_oddschecker_football_match($page_code, $element_pos); // extract match
				$match_string = $returned_array[0];
				log_write($match_string);
				$page_code = $returned_array[1];
				// append match to $csv_string
				$csv_string = $csv_string.$match_string."\n";
				// chop to end of date code block
				
				log_write("csv_string:\n$csv_string");
			}

			// at end of loop, check for another element in $page_code
			$next_element_type_array = find_next_element_type($page_code);
			// extract position and type variables
			$next_element_pos = $next_element_type_array[0];
			$next_element_type = $next_element_type_array[1];
		}
		
		log_write("At end of scrape_oddschecker_fixtures, csv_string is:\n$csv_string");
		
		return $csv_string;
	}
	// ==========================================================================================================
	// extract a date string from oddschecker page code
	// days begin with '<td class="day"', end with "</p>"
	function extract_oddschecker_date($page_code, $pos) {
	
		// chop up to $element_pos
		$page_code = substr($page_code, $element_pos);
		// find end of date, extract it, chop date string and closing tag out of page_code
		$current_pos = 1;
			while (substr($page_code, $current_pos, 4) != "</p>") {
				$current_pos++;
			}
			$date_string = substr($page_code, 0, $current_pos);
			log_write("Date extracted:");
			log_write($date);
			$date_string = ",,,".$date_string."\n"; // format $date_string for csv file
			$page_code = substr($page_code, $current_pos + 4);
				
		return array($date_line_string, $page_code);
	}
	// ==========================================================================================================
	// extract a league name from oddschecker page code
	// league names begins with '<td class="name"', end with "</p>"
	function extract_oddschecker_league_name($page_code, $element_pos)
	{
		// chop up to $element_pos
		$page_code = substr($page_code, $element_pos);
		// find end of league name, extract it, chop league name and closing tag out of page_code
		$current_pos = 1;
			while (substr($page_code, $current_pos, 4) != "</p>") {
				$current_pos++;
			}
			$league_name = substr($page_code, 0, $current_pos);
			log_write("League name extracted:");
			log_write($league_name);
			$league_name = ",,,".$date_string."\n"; // format league name for csv file
			$page_code = substr($page_code, $current_pos + 4);
				
		return array($league_name, $page_code);
	}
	// ==========================================================================================================
	// extract an oddschecker football match
	// code blocks containing matches start with '<tr data-market-id="' and end with "</tr>"
	function extract_oddschecker_football_match($page_code, $element_pos)
	{
		// chop up to $element_pos
		$page_code = substr($page_code, $element_pos);
		// time begins with '<td class="time">'
		$loc = strpos($page_code, '<td class="time">') + 17;
		$page_code = substr($page_code, $loc); // chop off everything including and before the time tag
		$page_code = trim($page_code);
		// move to <p> tag preceding the time
		$loc = 0;
		while (substr($page_code, $loc, 3) != "<p>") {
			$loc++;
		}
		$loc += 3;
		$page_code = substr($page_code, $loc); // chop off everything up to and including the <p> tag
		// move to <, chop preceding substring to get time
		$loc = 0;
		while (substr($page_code, $loc, 1) != "<") {
			$loc++;
		}
		$time_string = substr($page_code, 0, $loc);
		log_write("time_string = $time_string");
		
		// home team begins with '"fixtures-bet-name">', ends with "</span>"
		$loc = strpos($page_code, '"fixtures-bet-name">') + 20;
		$page_code = substr($page_code, $loc); // chop to beginning of home team name
		$end = strpos($page_code, "</span>");
		$home_team = substr($page_code, 0, $end);
		$page_code = substr($page_code, $end); // chop out home team name
		
		// get home price
		$start = strpos($page_code, "(");
		$page_code = substr($page_code, $start + 1); // chop off opening bracket before price plus everything before
		$end = strpos($page_code, ")");
		$home_price = substr($page_code, 0, $end); // extract home price
		$page_code = substr($page_code, $end + 1);
		log_write("home_price = $home_price");
		
		// extract draw price - preceded by 'span class="odds">'
		$start = strpos($page_code, 'span class="odds">') + 19;
		$page_code = substr($page_code, $start); // chop off everything before the draw price
		$end = strpos($page_code, "</span>");
		$draw_price = trim(substr($page_code, 0, $end));
		$draw_price = str_replace("(", "", $draw_price);
		$draw_price = str_replace(")", "", $draw_price);
		$page_code = substr($page_code, $end); // chop off draw price
		log_write("draw_price = $draw_price");
		
		// extract away team
		// away team name is preceded by '"fixtures-bet-name">'
		$start = strpos($page_code, '"fixtures-bet-name">' + 20);
		log_write("Chopping out away team...");
		log_write();
		
		
		
		$page_code = substr($page_code, $start); // chop off everything before the away team name
		$end = strpos($page_code, "</span>");
		$away_team = substr($page_code, 0, $end); // chop out away team
		$page_code = substr($page_code, $end + 7); // chop out away team and closing </span> tag
		log_write("away_team = $away_team");
		
		// extract away price
		$start = strpos($page_code, "(");
		$end = strpos($page_code, ")");
		$away_price = substr($page_code, $start + 1, ($end - $start));
		$away_price = str_replace("(", "", $away_price);
		$away_price = str_replace(")", "", $away_price);
		// chop up to the end of the away price
		$page_code = substr($page_code, $end + 1);
		log_write("away_price = $away_price");
		
		// construct match string for csv file
		$match_string = ",,,".$home_price.",".$home_team.",".$draw_price.",".$away_team.",".$away_price."\n";
		log_write("Match extracted:");
		log_write($match_string);
		
		return array($match_string, $page_code);
	}
	// ==========================================================================================================
	// find the type of the next element in $page_code
	// returns a two item array in the format (location_of_element, element_type)
	// element types are 0 for no element found, 1 for a date, 2 for a league name or 3 for a match
	function find_next_element_type ($page_code)
	{
		log_write("Just entered find_next_element_type()");
		$loc = -1; 			// -1 indicates no element found (default value)
		$element_type = -1; // -1 indicates no element found (default value)
	
		$locs = array(); // append three locations to this array
		$sentinel_strings = array('<td class="day"', '<td class="name"', '<tr data-market-id="');
		foreach ($sentinel_strings as $sentinel) {
			$sentinel_position = strpos($page_code, $sentinel);
			$locs[] = $sentinel_position;
			log_write("$sentinel found at position $sentinel_position");
		}
		
		// default "no element found" values
		$loc = -1;
		$element_type = -1;
		log_write("loc = $loc");
		
		for ($i = 0; $i < 3; $i++) {
		    // if $locs[$i] != -1 then this is an element has been found at this location
		    if ($locs[$i] != -1) {  // if this array element contains a found element location...
		        if ($loc == -1) {   // and $loc hasn't been set yet...
		            $loc = $locs[$i];   // set $loc to this found element location
					$element_type = $i + 1; // set element type to $i + 1
		        }
		        // if $loc has been set but $locs[$i] < $loc, set $loc to $locs[$i]
				// and set $element_type to $i + 1
		        // (condition that $locs[$i] != -1 has been checked in the outer loop)
		        if (($loc != -1) && ($locs[$i] < $loc)) {
					$loc = $locs[$i];
					$element_type = $i + 1;
		        }
		    }
		}
		log_write("At end of find_next_element_type,");
		log_write("loc = $loc");
		log_write("element_type = $element_type");
		log_write("============================================================");
		
		$array_to_return = array($loc, $element_type);
		return $array_to_return;
	}
	// =================================================================================================================
	// function to download a webpage
	function get_url_contents($url)
	{
        $crl = curl_init();
        $timeout = 5;
        curl_setopt($crl,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        curl_setopt ($crl, CURLOPT_URL,$url);
        curl_setopt ($crl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
        $ret = curl_exec($crl);
        curl_close($crl);
        return $ret;
	}
	// =================================================================================================================
	
	// logging function - used for testing
	function log_write($text)
	{
		$log_file = fopen("log_file.txt", "a");
		// get time string
		// $timestamp = time();
		$time_string = date("d/m/y H:i:s");
		// build write_string
		$write_string = $time_string." - ".$text."\r\n";
		fwrite($log_file, $write_string);
		return;
	}
	// =================================================================================================================
	// format names for participant loader by replacing the last space with a comma
	// if the name doesn't not contain a space, prepend a comma to the name
	function format_names_for_participant_loader($text)
	{
		// split text along newlines
		$lines = explode("\n", $text);
		
		// filter out lines containing only whitespace
		$filtered_lines = array();
		foreach ($lines as $line) {
			if (!trim($line) == "")
			$filtered_lines[] = $line;
		}
		$names = $filtered_lines;
		
		// loop through $names
		$names_with_commas = array(); // array to add reformated names with commas to
		foreach($names as $name) {

			// trim each element
			$name = trim($name);
			// if no space in name, prepend a comma, append reformatted name to $names_with_commas,
			// otherwise rebuild the name, replacing the final space with a comma before adding it
			// to $names_with_commas
			if (strpos($name, " ") == false) {
				$name = ",".$name;				// prepend commas
				$names_with_commas[] = $name;	// add to array
			} else {
				// find final space location
				$name_length = strlen($name);
				$pos = $name_length - 1;
				while ($name{$pos} != ' ') {
					$pos = $pos - 1;
				}
				// find first bit of name
				$first_bit_of_name = substr($name, 0, $pos);
				// find last bit of name
				$last_bit_of_name = substr($name, $pos + 1);
				// rebuild name
				$rebuilt_name = $first_bit_of_name.",".$last_bit_of_name;
				// add name to array
				$names_with_commas[] = $rebuilt_name;
			}
		}

		// construct $write_string from $names_with_commas
		$write_string = "";
		foreach($names_with_commas as $name) {
			$write_string = $write_string.$name;
			$write_string = $write_string."\n<br>\n";
		}
		
		return $write_string;
	}
	// ===============================================================================================================
	// extract scorer names from boylesports copy and paste of webpage text
	// add a comma in place of the final space in the name
	// if no space in name, prepend a comma
	function extract_boylesports_goalscorer_names($text)
	{
		$big_write_string = ""; // append extracted scorers and team names here
		$text_lines = explode("\n", $text);
		foreach ($text_lines as $line) {
			// echo "$line<br>";
			// scorer names both begin and end with a tab
			if ((!strpos($line, "complete market") === false) || (!strpos($line, "Goalscorer") === false)) {
				continue;
			}
			if ((substr($line, 0, 1) == "\t") && (substr($line, strlen($line) -2, 1) == "\t")) {
				$scorer_name = add_comma_to_name($line);
				if (strlen($scorer_name) > 2) {
					$big_write_string = $big_write_string.$scorer_name."<br>\n";
				}
			}
			// team names don't start with a tab, end with a tab
			if ((substr($line, 0, 1) != "\t") && (substr($line, strlen($line) -2, 1) == "\t")) {
				$team_name = trim($line);
				$big_write_string = $big_write_string."<br>\n===== ".$team_name." =====<br>\n";
			}
		}
		return $big_write_string;
	}
	// =======================================================================================================
	// helper function
	// if there's a space in a name, replaces the final space with a comma,
	// otherwise prepends a comma to name
	function add_comma_to_name($name)
	{
		$name = trim($name);
		if (strpos($name, " ") === false) {
			$name = ",".$name;
		} else { 		// there's a space in the name
			// find final space in name
			$pos = strlen($name) - 1;
			while (substr($name, $pos, 1) != " ") {
				$pos--;
			}
			// rebuild name with comma instead of final space
			$first_part_of_name = substr($name, 0, $pos);
			$second_part_of_name = substr($name, $pos + 1, strlen($name) - $pos);
			$name = $first_part_of_name.",".$second_part_of_name;
		}
		if (strlen($name) > 2) {
			// echo "$name";
			return $name;
		} else {
			return "";
		}
	}
	// =====================================================================================================
# download a webpage from $url and save it as $local_filename
# If $return_data = 1, return the contents of the webpage, otherwise simply return
function download_webpage($url, $local_filename, $return_data) {
	$ch = curl_init();
	$timeout = 20;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

	# spoof the user agent!
	$spoof_user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13)';
	$spoof_user_agent .= ' Gecko/20080311 Firefox/2.0.0.13';
	curl_setopt($ch,CURLOPT_USERAGENT, $spoof_user_agent);
	
	$data = curl_exec($ch);
	curl_close($ch);
	# return $data;
	
	# save the webpage content in a local file
	$outfile = fopen($local_filename, 'w');
	fwrite($outfile, $data);
	fclose($outfile);
	
	if ($return_data == 1) {
		return $data;
	} else {
		return;
	}
	
}
# ==============================================================================================
?>