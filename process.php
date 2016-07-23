<?php
//config
define('MAX_ISSUES_PER_PAGE',100);
define('MAX_PAGES',10);
error_reporting(E_ALL); ini_set("display_errors", "On");
date_default_timezone_set('UTC');

//imports
require_once(__DIR__ . '/github-php-client-master/client/GitHubClient.php');

//helper functions

//gets current date time in required format
function getCurrentDateTimeInRequiredFormat(){
	$dt = new DateTime();
	return $dt->format('Y-m-d\TH-i-s.\0\0\0\Z');
}

//gets date time in required format 24 hours before current datetime
function getDateTime24HoursBefore(){
	$dt = $date = (new \DateTime())->modify('-24 hours');
	return $dt->format('Y-m-d\TH-i-s.\0\0\0\Z');
}

//gets datetime a week before
function getDateTimeAWeekBefore(){
	$dt = $date = (new \DateTime())->modify('-168 hours');
	return $dt->format('Y-m-d\TH-i-s.\0\0\0\Z');
}


//gets path components from the url
function getPathComponentsFromRepoUrl()
{
	//parse the public url of the repo
	//kill if empty param
	if(empty($_GET['repo_url'])){
		die('Invalid params or no params');
	}
	$url = urldecode($_GET['repo_url']);
	//if empty, kill it
	if(!$url){
		die('No url parameter passed');
	}

	$parsed_url = parse_url($url);
	$path = $parsed_url['path'];

	//if url path is empty, kill it
	if(!$path){
		die('Incomplete url');
	}

	$path_components = explode('/',$path);
	//if owner and repo doesn't exist, kill it
	if(empty($path_components[1]) || empty($path_components[2])){
		die('Incomplete github url');
	}

	return $path_components;
}

//getting path components
$path_components = getPathComponentsFromRepoUrl();
$owner = $path_components[1];
$repo = $path_components[2];

//initialising variables
$client = new GitHubClient();
$allIssues = [];
$page_no = 1;
$total_open_issues = 0;
$total_open_issues_last_24_hours = 0;
$total_open_issues_before_24_less_than_7_days = 0;
$total_open_issues_more_than_7_days = 0;
$curr_dt = getCurrentDateTimeInRequiredFormat();
$dt_before_24_hours = getDateTime24HoursBefore();
$dt_a_week_before= getDateTimeAWeekBefore();



while($page_no < MAX_PAGES){
	$client->setPage($page_no);
	$client->setPageSize(MAX_ISSUES_PER_PAGE);
	$issues = $client->issues->listIssues($owner, $repo);
	if(count($issues) == 0){
		break;
	}
	else{
		foreach ($issues as $issue) {
			$allIssues[] = $issue;
		}
	}
	$page_no++;
}


if($allIssues){
	//required computations
	foreach ($allIssues as $issue) {
		//computing open issues
		if($issue->getState() == 'open'){
			$total_open_issues++;
		}
		//computing issues opened in last 24 hours
		if($issue->getCreatedAt() > $dt_before_24_hours && $issue->getState() == 'open'){
			$total_open_issues_last_24_hours++;
		}
		//computing open issues that were opened more than 24 hours ago but less than 7 days ago
		if($issue->getCreatedAt() < $dt_before_24_hours && $issue->getCreatedAt() > $dt_a_week_before && $issue->getState() == 'open'){
			$total_open_issues_before_24_less_than_7_days++;
		}
		//computing issues opened more than 7 days ago
		if($issue->getCreatedAt() < $dt_a_week_before && $issue->getState() == 'open'){
			$total_open_issues_more_than_7_days++;
		}

	}
}

echo "Total number of open issues: ".$total_open_issues."<br>";
echo "Number of open issues that were opened in the last 24 hours: ".$total_open_issues_last_24_hours."<br>";
echo "Number of open issues that were opened more than 24 hours ago but less than 7 days ago: ".$total_open_issues_before_24_less_than_7_days."<br>";
echo "Number of open issues that were opened more than 7 days ago  ".$total_open_issues_more_than_7_days."<br>";


?>