
<?php
require_once ('lib/MockCI.php');
require_once ('lib/JenkinsCI.php');
require_once ('lib/Exceptions.php');

function isort($a,$b) {
    return ($a['timeElapse']) > ($b['timeElapse']);
}

$result = '';
if (isset($_GET['view'])) {
	$ci = new JenkinsCI('http://continuousintegration.corp.ltutech.com', $_GET['view']);
} else {
	$ci = new JenkinsCI('http://continuousintegration.corp.ltutech.com');
}

try {
	$jobs = $ci->getAllJobs();
} catch (BuildiatorCIServerCommunicationException $e) {
	$result = array('status'  => 'error',
					'content' => $e->getMessage());
}

if (!is_array($result)) {
	$html = '';
  uksort($jobs, "isort");

  $jobsFailed = array();
  $jobsUnstable = array();
  $jobsStable = array();
  foreach ($jobs as $job) {
    $lsStatus = $job['status'][0];
    if ($lsStatus == 'failed') {
      $jobsFailed[] = $job;
    }
    if ($lsStatus == 'unstable') {
      $jobsUnstable[] = $job;
    }
    if ((($lsStatus == 'successful') or ($lsStatus == 'cancelled')) or ($lsStatus == 'disabled')) {
      $jobsStable[] = $job;
      }
  }

  foreach ($jobsFailed as $job) {
 		$blame = null;
 		$claim = null;
		$status = $job['status'];
		if (!empty($job['blame'])) {
			$culprit = $job['blame'];
			array_push($job['status'], "$culprit blamed");
			$blame = "<span class='blame'>{$job['blame']}</span>" ;
		}
		if (!empty($job['claim'])) {
			$culprit = $job['claim'];
			array_push($job['status'], "$culprit claimed");
			$claim = "<span class='claim' >{$job['claim']}</span>" ;
		}
		if ($job['status'][0] != 'successful' ) {
				$html .="<li class = 'jobBroken " . implode(" ",$job['status'] ) . "'>{$job['name']}${claim}{$blame}</li><li class = 'lastSuccedBuild '>{$job['lastSuccessfulBuildTime']}\n</li>";
    }
	}

  $html .="<li class = 'jobsBorderHeader '>SuccessfullBuild</li>";
  $html .="<p>=============================</p>";

  foreach ($jobsStable as $job) {
		$html .="<li class = 'jobSuccess " . implode(" ",$job['status'] ) . "'>{$job['name']}${claim}{$blame}</li>";
	}

	$result = array('status' => 'ok', 'content' => $html);
}

echo json_encode($result);
?>
