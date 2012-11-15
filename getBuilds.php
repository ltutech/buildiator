<?php
require_once ('lib/MockCI.php');
require_once ('lib/JenkinsCI.php');
require_once ('lib/Exceptions.php');

$result = '';
if (isset($_GET['view'])) {
	$ci = new JenkinsCI($_GET['view']);
} else {
	$ci = new JenkinsCI();
}

try {
	$jobs = $ci->getAllJobs();
} catch (BuildiatorCIServerCommunicationException $e) {
	$result = array('status'  => 'error',
					'content' => $e->getMessage());
}

if (!is_array($result)) {
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
			$claim = "<span class='claim' >{$job['claim']}</span>" ;
		}
		$html .="<li class = 'job " . implode(" ",$job['status'] ) . "'>{$job['name']}${claim}{$blame}</li>";
	}

	$result = array('status'  => 'ok',
					'content' => $html);
}

echo json_encode($result);
?>
