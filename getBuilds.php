
<?php
require_once ('lib/MockCI.php');
require_once ('lib/JenkinsCI.php');
require_once ('lib/Exceptions.php');

function isort($a,$b) {
    return ($a['timeElapse']) > ($b['timeElapse']);
}

function displayJobsProblem($jobs)
{
  $html = '';
  foreach ($jobs as $job) {
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
      $claim = '<img src="https://account.corp.ltutech.com/photos.php?user=' . $culprit .'" height="60"  style="float:right">';
    }
    $html .="<li class = 'jobBroken " . implode(" ",$job['status'] ) . "'>{$job['name']}</li><li class = 'lastSuccedBuild '>{$job['lastSuccessfulBuildTime']}{$claim}\n</li>";
  }
  return $html;
}

function displayJobsBorder($countJobs, $countJobsStable)
{
  $html = '';
  $html .="<li class = 'jobsBorderHeader '>Successful Builds: $countJobsStable/$countJobs</li>";
  return $html;
}

function displayJobsSuccess($jobsStable)
{
  $html = '';
  foreach ($jobsStable as $job) {
    $html .="<li class = 'jobSuccess " . implode(" ",$job['status'] ) . "'>{$job['name']}</li>";
  }
  return $html;
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
  $result = array('status'  => 'error',	'content' => $e->getMessage());
}

if (!is_array($result)) {
  $html = '';

  $jobsFailed = array();
  $jobsUnstable = array();
  $jobsCancel = array();
  $jobsStable = array();
  foreach ($jobs as $job) {
    $lsStatus = $job['status'][0];
    if ($lsStatus == 'failed') {
      $jobsFailed[] = $job;
    }
    if ($lsStatus == 'unstable') {
      $jobsUnstable[] = $job;
    }
    if (($lsStatus == 'cancelled')  or ($lsStatus == 'disabled')) {
      $jobsCancel[] = $job;
    }
    if ($lsStatus == 'successful') {
      $jobsStable[] = $job;
    }
  }

  usort($jobsFailed, "isort");
  usort($jobsUnstable, "isort");
  usort($jobsCancel, "isort");

  $html .= displayJobsProblem($jobsFailed);
  $html .= displayJobsProblem($jobsUnstable);
  $html .= displayJobsProblem($jobsCancel);
  $html .= displayJobsBorder(count($jobs), count($jobsStable));
  $html .= displayJobsSuccess($jobsStable);

  $result = array('status' => 'ok', 'content' => $html);
}

echo json_encode($result);
?>
