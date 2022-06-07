<?php
	#
	# $Id: commit.php,v 1.5 2013-04-08 12:15:34 dan Exp $
	#
	# Copyright (c) 1998-2006 DVL Software Limited
	#


// base class for a single commit
class Commit {

	var $dbh;

	var $commit_log_id;
	var $commit_date_raw;
	var $encoding_losses;
	var $message_id;
	var $commit_hash_short;
	var $committer;
	var $committer_name;
	var $committer_email;
	var $author_name;
	var $author_email;
	var $commit_description;
	var $commit_date;
	var $commit_time;
	var $date_added;
	var $stf_message;
	var $svn_revision;
	var $repository;
	var $repo_hostname;
	var $path_to_repo;
	var $branch;

	var $last_commit_date;

	var $LocalResult;

	# the message_id for all the emails which originated from subversion contain freebsd.org
	# For git commits, we put the full has into message_id . Commits from git do not contain that value.
	# This is used to decide if commits are from svn or from git
	const MESSAGE_ID_DOMAIN = 'freebsd.org';

	function __construct($dbh) {
		$this->dbh	= $dbh;
	}

	function PopulateValues($myrow) {
		$this->commit_log_id      = $myrow["commit_log_id"];
		$this->commit_date_raw    = $myrow["commit_date_raw"];
		$this->encoding_losses    = $myrow["encoding_losses"];
		$this->message_id         = $myrow["message_id"];
		$this->commit_hash_short  = $myrow["commit_hash_short"];
		$this->committer          = $myrow["committer"];
		$this->committer_name     = $myrow["committer_name"];
		$this->committer_email    = $myrow["committer_email"];
		$this->author_name        = $myrow["author_name"];
		$this->author_email       = $myrow["author_email"];
		$this->commit_description = $myrow["commit_description"];
		$this->commit_date        = $myrow["commit_date"];
		$this->commit_time        = $myrow["commit_time"];
		$this->date_added         = $myrow["date_added"];
		$this->stf_message        = $myrow["stf_message"];
		$this->svn_revision       = $myrow["svn_revision"];
		$this->repository         = $myrow["repository"];
		$this->repo_hostname      = $myrow["repo_hostname"];
		$this->path_to_repo       = $myrow["path_to_repo"];
		$this->branch             = $myrow["branch"];

		$this->last_commit_date   = $myrow["last_commit_date"];
	}

	function FetchNth($N) {
		#
		# call FetchByCategoryInitialise first.
		# then call this function N times, where N is the number
		# returned by FetchByCategoryInitialise
		#

		$myrow = pg_fetch_assoc($this->LocalResult, $N);
		$this->PopulateValues($myrow);
	}


	function FetchByMessageId($message_id) {
	        $Debug = 0;
		$Where = "message_id = '" . pg_escape_string($message_id) . "'";

		$result = $this->FetchByIDHelper($Where);
		
		if ($result) {
			$numrows = pg_num_rows($result);
			if ($numrows == 1) {
				if ($Debug) echo "fetched by ID succeeded<BR>";
				$myrow = pg_fetch_array ($result, 0);
				$this->PopulateValues($myrow);
			}
		}		if ($Debug) echo 'message_id is ' . $this->message_id;
		return $this->message_id;
	}

	function FetchById($commit_log_id) {
	        $Debug = 0;
		$Where = "CL.id = " . pg_escape_string($commit_log_id);

		$result = $this->FetchByIDHelper($Where);

		if ($result) {
			$numrows = pg_num_rows($result);
			if ($numrows == 1) {
				if ($Debug) echo "fetched by ID succeeded<BR>";
				$myrow = pg_fetch_array ($result, 0);
				$this->PopulateValues($myrow);
			}
		}

		if ($Debug) echo 'message_id is ' . $this->message_id;
		return $this->message_id;
	}

	function FetchByRevision($revision) {
		# treat r362638 and 362638 the same
		# We don't use r internally, so strip it.
		if (strtolower(substr($revision, 0, 1)) == 'r') {
			$revision = (substr($revision, 1));
		}
		$Where = "svn_revision = '" . pg_escape_string($revision) . "'";

		$result = $this->FetchByIDHelper($Where);
		
		if ($result) {
			$numrows = pg_num_rows($result);
			switch($numrows)
			{
                          case 0:
                                break;
                                
                          default:
                                // assume one or more rows
                                // more thane one email generated by that revision
                                // store this result for late iteration
                                $this->LocalResult = $result;
                                $message_ids = array();
                		for ($i = 0; $i < $numrows; $i++) {
                			$myrow = pg_fetch_array($result, $i);
                			$message_ids[] = $myrow['message_id'];
                                }
                                return $message_ids;
                                break;
                        }
                                
                                
		}
		if ($Debug) echo 'message_id is ' . $this->message_id;

		return $this->message_id;
	}

	protected function FetchByIDHelper($Where) {
		$Debug = 0;

		$sql = "
SELECT CL.id as commit_log_id,
       message_id,
       commit_hash_short,
       message_date,
       CL.commit_date - SystemTimeAdjust()               AS commit_date_raw,
       to_char(commit_date - SystemTimeAdjust(), 'DD Mon YYYY')  as commit_date,
       to_char(commit_date - SystemTimeAdjust(), 'HH24:MI:SS')   as commit_time,
       message_subject,
       date_added,
       committer,
       committer_name,
       committer_email,
       author_name,
       author_email,
       CL.description AS commit_description,
       CL.system_id,
       svn_revision,
       R.repository,
       R.repo_hostname,
       R.path_to_repo,
       encoding_losses,
       GMT_Format(CL.commit_date) as last_commit_date,
       STF.message as stf_message,
       SB.branch_name AS branch
  FROM commit_log CL LEFT OUTER JOIN sanity_test_failures STF ON CL.id         = STF.commit_log_id
                                JOIN commit_log_branches  CLB ON CL.id         = CLB.commit_log_id
                                JOIN system_branch        SB  ON CLB.branch_id = SB.id
    LEFT OUTER JOIN repo R ON CL.repo_id = R.id
 WHERE " . $Where;


    if ($Debug) echo "sql = '<pre>$sql</pre>'<BR>";

		$result = pg_exec($this->dbh, $sql);
		return $result;
	}

	function DateNewestPort() {
		$Debug = 0;

		$sql = "
SELECT GMT_Format(CL.commit_date) as last_commit_date
  FROM commit_log_ports CLP
  JOIN commit_log       CL on CL.id = CLP.commit_log_id
 ORDER BY CL.commit_date DESC
  LIMIT 1";
#		echo "sql = '<pre>$sql</pre>'<BR>";

		$result = pg_exec($this->dbh, $sql);
		if ($result) {
			$numrows = pg_num_rows($result);
			if ($numrows == 1) {
				if ($Debug) echo "fetched by ID succeeded<BR>";
				$myrow = pg_fetch_array ($result, 0);
				$this->PopulateValues($myrow);
			}
		}

		return $this->message_id;
	}

	function EncodingLosses() {
		return $this->encoding_losses == 't';
	}

}
