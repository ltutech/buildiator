<?php
/**
 * Handles communication with a JenkinsCI server
 *
 * @author Jake Worrell (jakeworrell.co.uk)
 */

require_once 'base/ContinuousIntegrationServerInterface.php';
require_once 'lib/Exceptions.php';

class JenkinsCI implements ContinuousIntegrationServerInterface{
	private $url;

	function __construct($url = null, $view = null) {
		if ($url==null) {
			$url = 'http://' . gethostname() . ':8080';
		}
		$this->url = $url;
		if ($view!=null) {
			$this->view = '/view/' . $view;
		} else {
			$this->view = '';
		}
	}

	public function getAllJobs() {
		$json = @file_get_contents($this->url . $this->view .'/api/json?tree=jobs[name,color,url]');
		if (!$json) {
			throw new BuildiatorCIServerCommunicationException ("Error getting build data from Jenkins server at {$this->url}");
		}
		$jobs = json_decode($json);
		foreach ($jobs->jobs as $job) {
			$newjob = array('name'=>$job->name,
                      'status'=>$this->translateColorToStatus($job->color),
                      'url'=>$job->url);
      if ($newjob['status'][0] == 'failed' or $newjob['status'][0] == 'unstable') {
				$newjob['blame'] = $this->getBlameFor($job->name);
				$newjob['claim'] = $this->getClaimant($job->name);
        $timeElapse = $this->getFirstUnsuccessfulBuild($job->name);
        $newjob['timeElapse'] = $timeElapse;
        $newjob['lastSuccessfulBuildTime'] = $this->humanTiming ($timeElapse);
			}
			$return[] = $newjob;
		}
		return ($return);
	}

  public function humanTiming ($time)
  {
    $time = time() - $time / 1000; // to get the time in second since that moment

    $tokens = array (
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    );

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
    }
  }


	public function getFirstUnsuccessfulBuild($jobName) {
    $job = rawurlencode($jobName);
		$json = file_get_contents($this->url . "/job/{$job}/api/json?tree=lastStableBuild[number]");
		$lastStableBuild = json_decode($json);
    $number = $lastStableBuild->lastStableBuild->number + 1;
    $json2 = file_get_contents($this->url . "/job/{$job}/{$number}/api/json?tree=timestamp");
    // If the number doesn't refers to any existing build (It has been remove by the user)
    // The timestanp return will be the one of the last successfull build
    if (!$json2) {
      $number = $lastStableBuild->lastStableBuild->number;
      $json2 = file_get_contents($this->url . "/job/{$job}/{$number}/api/json?tree=timestamp");
    }
    $firstUnsuccessfulBuild = json_decode($json2);
    return $firstUnsuccessfulBuild->timestamp;
}

	private function getBlameFor($jobName) {
		$job = rawurlencode($jobName);
		$json = file_get_contents($this->url . "/job/{$job}/lastBuild/api/json?tree=culprits[fullName]");
		$culprits = json_decode($json);

		if (empty($culprits->culprits)) {
			return "Unknown";
		}
		return $culprits->culprits[0]->fullName;

	}

	private function getClaimant($jobName) {
		$job = rawurlencode($jobName);
		$json = file_get_contents($this->url . "/job/{$job}/lastBuild/api/json?tree=actions[claimed,claimedBy,reason]");
		$actions = json_decode($json);
		foreach ($actions->actions as $action) {
       if (isset($action->claimed)) {
           if ($action->claimed) {
              return $action->claimedBy;
           }
        }
		}
		return null;
	}

	private function translateColorToStatus($color) {
		switch($color){
			case 'blue':
				return array('successful');
			case 'blue_anime':
				return array('successful','building');
			case 'red':
				return array('failed');
			case 'red_anime':
				return array('failed','building');
			case 'yellow':
				return array('unstable');
			case 'yellow_anime':
				return array('unstable', 'building');
			case 'aborted':
				return array('cancelled');
			case 'aborted_anime':
				return array('cancelled','building');
			case 'disabled':
				return array('disabled');
			default:
				return array('unknown');
		}
	}
}

?>
