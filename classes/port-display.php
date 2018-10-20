<?php
	#
	# $Id: port-display.php,v 1.20 2013-03-25 16:09:08 dan Exp $
	#
	# Copyright (c) 2005-2006 DVL Software Limited
	#
	
require_once($_SERVER['DOCUMENT_ROOT'] . '/../classes/master_slave.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/../classes/port_dependencies.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/../classes/port_configure_plist.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/../include/htmlify.php');

define('port_display_WATCH_LIST_ADD_REMOVE', '%%%$$$WATCHLIST$$$%%%');
define('port_display_AD',                    '%%%$$$ADGOESHERE$$$%%%');
define('DEPENDS_SUMMARY', 7 );
define('PLIST_SUMMARY',   0 );

class port_display {

	var $db;

	protected $port;
	protected $branch;

	var $User;		# used for matching against watch lists
	var $DaysMarkedAsNew;

	var $ShowEverything;

	var $LinkToPort;
	var $ShowAd;
	var $ShowCategory;
	var $ShowChangesLink;
	var $ShowDateAdded;
	var $ShowDescriptionShort;
	var $ShowDescriptionLong;
	var $ShowDescriptionLink;
	var $ShowDepends;
	var $ShowDownloadPortLink;
	var $ShowHomepageLink;
	var $ShowLastChange;        # who made the last change - useful for knowing last commit
	var $ShowLastCommitDate;    # when was the last change - useful when searching by committer
	var $ShowMaintainedBy;
	var $ShowMasterSites;
	var $ShowMasterSlave;
	var $ShowPackageLink;
	var $ShowPortCreationDate;
	var $ShowShortDescription;
	var $ShowWatchListCount;
	var $ShowWatchListStatus;
	var $ShowDistInfo;

	function __construct(&$db, $User = 0) {
		$this->db   = $db;
		$this->User = $User;
		$this->DaysMarkedAsNew = 10;

		$this->SetDetailsNil();
	}

	function htmlConflicts($conflicts) {
	  $HTML = '';

	  $HTML .= "<ul>\n";
	  $data = preg_split('/\s+/', $conflicts);
	  foreach($data as $item) {
	    $HTML .= '<li>' . $item . "</li>\n";
	  }
	  $HTML .= "</ul>\n";

	  return $HTML;	
	}

	function SetPort($port, $branch = BRANCH_HEAD) {
	  //
	  // We could derived branch from element_pathname(port->element_id) but let's try passing in branch explicity.
	  //
	  $this->port   = $port;
	  $this->branch = $branch;
	}

	function link_to_repo() {
          # we want something like
          # http://svn.freebsd.org/ports/head/x11-wm/awesome/
          $link_title = 'SVNWeb';
          $link = 'https://';
          if (!empty($this->port->svn_hostname)) {
            $link .= $this->port->svn_hostname;
          } else {
            $link .= DEFAULT_SVN_REPO;
          }

          $link .= $this->port->element_pathname . '/';
          if ($this->port->IsDeleted()) {
            #
	    # If the port has been deleted, let's link to the last commit.
	    # Deleted ports don't change much.  It's easier to do this here
	    # than to do it for ALL ports.
	    #
            require_once($_SERVER['DOCUMENT_ROOT'] . '/../classes/commit.php');

            $commit = new Commit($this->db);
            $commit->FetchById($this->port->last_commit_id);

            if (!empty($commit->svn_revision)) {
              $link .= '?pathrev=' . ($commit->svn_revision - 1);
            } else {
              # if there is no last revision, we can't link to it.
	      $link = null;
            }
          }

          if (!empty($link)) {
            $link = '<a href="' . $link . '">' . $link_title . '</a>';
          } else {
            $link = '<strike>SVNWeb</strike>';
          }

          return $link;
	}

	function SetDetailsNil() {
		$this->ShowEverything          = false;

 		$this->LinkToPort              = false;
 		$this->ShowAd                  = false;
		$this->ShowCategory            = false;
		$this->ShowChangesLink         = false;
		$this->ShowDateAdded           = false;
		$this->ShowDescriptionShort    = false;
		$this->ShowDescriptionLong     = false;
		$this->ShowDescriptionLink     = false;
		$this->ShowDepends             = false;
		$this->ShowDownloadPortLink    = false;
		$this->ShowHomepageLink        = false;
		$this->ShowLastChange          = false;
		$this->ShowLastCommitDate      = false;
		$this->ShowMaintainedBy        = false;
		$this->ShowMasterSites         = false;
		$this->ShowMasterSlave         = false;
		$this->ShowPackageLink         = false;
		$this->ShowPortCreationDate    = false;
		$this->ShowPortsMonLink        = false;
		$this->ShowConfigurePlist      = false;
		$this->ShowShortDescription    = false;
		$this->ShowWatchListCount      = false;
		$this->ShowWatchListStatus     = false;
		$this->ShowDistInfo            = false;
	}

	function SetDetailsFull() {
		$this->SetDetailsNil();
		$this->ShowEverything = true;
	}

	function SetDetailsSearch() {
		$this->SetDetailsNil();
 		$this->LinkToPort              = true;
		$this->ShowCategory            = true;
		$this->ShowChangesLink         = true;
		$this->ShowDescriptionLink     = true;
		$this->ShowDownloadPortLink    = true;
		$this->ShowHomepageLink        = true;
		$this->ShowMaintainedBy        = true;
		$this->ShowPortCreationDate    = true;
		$this->ShowPortsMonLink        = true;
		$this->ShowPackageLink         = true;
		$this->ShowShortDescription    = true;
		$this->ShowWatchListStatus     = true;
		$this->ShowLastCommitDate      = true;
	}

	function SetDetailsReports() {
		$this->SetDetailsNil();
		$this->SetDetailsSearch();

		$this->ShowDateAdded = true;
	}

	function SetDetailsWatchList() {
		$this->SetDetailsNil();
		$this->SetDetailsSearch();

		$this->ShowDateAdded  = true;
		$this->ShowLastChange = true;
	}

	function SetDetailsCategory() {
		$this->SetDetailsNil();

 		$this->LinkToPort           = true;
		$this->ShowDescriptionLink  = true;
		$this->ShowMaintainedBy     = true;
		$this->ShowPortCreationDate = true;
		$this->ShowShortDescription = true;
		$this->ShowWatchListStatus  = true;
		$this->ShowDateAdded        = true;
	}

	function SetDetailsIndex() {
		$this->SetDetailsNil();

 		$this->LinkToPort           = true;
		$this->ShowDescriptionLink  = true;
		$this->ShowMaintainedBy     = true;
		$this->ShowPortCreationDate = true;
		$this->ShowShortDescription = true;
		$this->ShowWatchListStatus  = true;
		$this->ShowDateAdded        = true;
	}
	
	function JavascriptInclude()
	{
	  return '
	  <script type="text/javascript" src="/javascript/jquery-1.5.min.js"></script>
	  <script type="text/javascript" src="/javascript/freshports.js"></script>
';	  
	}

	function DisplayPlainText() {
		$result = $this->port->category . '/' . $this->port->port;

		return $result;
	}

	function Display($verbosity_level = 1) {

		# verbosity_level has been defined, but not used.
		$port = $this->port;

		$HTML = '';
#		$HTML = $this->JavascriptInclude();

		$MarkedAsNew = "N";
		$HTML .= "<DL>\n";
		
		$HTML .= "<DT>";

		$HTML .= port_display_WATCH_LIST_ADD_REMOVE;

		$HTML .= '<BIG><B>';

		if ($this->LinkToPort) {
			$HTML .= $this->LinkToPort();
		} else {
			$HTML .= $port->port;
		}

		$HTML .= "</B></BIG>";

		// description
		if ($port->short_description && ($this->ShowShortDescription || $this->ShowEverything)) {
			$HTML .= ' <span class="fp_description_short">' . htmlify(_forDisplay($port->short_description)) . '</span>';
			$HTML .= "<br>\n";
		}

		$HTML .= "<b>";
		$PackageVersion = freshports_PackageVersion($port->{'version'}, $port->{'revision'}, $port->{'epoch'});
		if (strlen($PackageVersion) > 0) {
			$HTML .= ' ' . $PackageVersion;
		}

		if (IsSet($port->category_looking_at)) {
			if ($port->category_looking_at != $port->category) {
				$HTML .= '<sup>*</sup>';
			}
		}

		$HTML .= "</b>";

		if ($this->ShowEverything || $this->ShowCategory) {
			$HTML .= ' <A HREF="/' . $port->category . '/" TITLE="The category for this port">' . $port->category . '</A>';
		}

		// indicate if this port has been removed from cvs
		if ($port->IsDeleted()) {
			$HTML .= " " . freshports_Deleted_Icon_Link() . "\n";
		}

		// indicate if this port needs refreshing from CVS
		if ($port->{'needs_refresh'}) {
			$HTML .= " " . freshports_Refresh_Icon_Link() . "\n";
		}

		if ($port->{'date_added'} > Time() - 3600 * 24 * $this->DaysMarkedAsNew) {
			$MarkedAsNew = "Y";
			$HTML .= freshports_New_Icon() . "\n";
		}

		if ($this->ShowEverything || $this->ShowWatchListCount) {
			$HTML .= ' ' . freshPorts_WatchListCount_Icon_Link() . '=' . $port->WatchListCount();
		}

		$HTML .= ' ' . freshports_Search_Depends_All($port->category . '/' . $port->port);

		# if this port is, or every has been, vulnerable, display the right skull
		# and a link to the list of all such vulnerabilities
		if ($port->IsVulnerable() || $port->WasVulnerable()) {
			$HTML .= ' ' . freshports_VuXML_Link($port->package_name, $port->IsVulnerable());
		}

		# search for bugs related to this port
		$HTML .= ' ' . freshports_Search_For_Bugs($port->category . '/' . $port->port);

		# report a bug related to this port
		$HTML .= ' ' . freshports_Report_A_Bug($port->category . '/' . $port->port);

		$HTML .= "</DT>\n<DD>";
		# show forbidden and broken
		if ($port->forbidden) {
			$HTML .= freshports_Forbidden_Icon_Link($port->forbidden)   . ' FORBIDDEN: '  . htmlify(_forDisplay($port->forbidden))  . "<br>";
		}

		if ($port->broken) {
			$HTML .= freshports_Broken_Icon_Link($port->broken)         . ' BROKEN: '     . htmlify(_forDisplay($port->broken))     . "<br>"; ;
		}

		if ($port->deprecated) {
			$HTML .= freshports_Deprecated_Icon_Link($port->deprecated) . ' DEPRECATED: ' . htmlify(_forDisplay($port->deprecated)) . "<br>"; ;
		}

		if ($port->expiration_date) {
			if (date('Y-m-d') >= $port->expiration_date) {
				$HTML .= freshports_Expired_Icon_Link($port->expiration_date) . ' This port expired on: ' . $port->expiration_date . '<br>';
			} else {
				$HTML .= freshports_Expiration_Icon_Link($port->expiration_date) . ' EXPIRATION DATE: ' . $port->expiration_date . '<br>';
			}
		}

		if ($port->ignore) {
			$HTML .= freshports_Ignore_Icon_Link($port->ignore)         . ' IGNORE: '     . htmlify(_forDisplay($port->ignore))     . "<br>"; ;
		}

		if ($port->restricted) {
			$HTML .= freshports_Restricted_Icon_Link($port->restricted) . ' RESTRICTED: '     . htmlify(_forDisplay($port->restricted)) . '<br>';
		}

		if ($port->no_cdrom) {
			$HTML .= freshports_No_CDROM_Icon_Link($port->no_cdrom)      . ' NO CDROM: '     . htmlify(_forDisplay($port->no_cdrom))   . '<br>';
		}

		if ($port->is_interactive) {
			$HTML .= freshports_Is_Interactive_Icon_Link($port->is_interactive) . ' IS INTERACTIVE: '  . htmlify(_forDisplay($port->is_interactive)) . '<br>';
		}

		// maintainer
		if ($port->maintainer && ($this->ShowMaintainedBy || $this->ShowEverything)) {
			if (strtolower($port->maintainer) == UNMAINTAINTED_ADDRESS) {
				$HTML .= '<br>There is no maintainer for this port.<br>';
				$HTML .= 'Any concerns regarding this port should be directed to the FreeBSD ' .
				         'Ports mailing list via ';
				$HTML .= '<A HREF="' . MAILTO . ':' . freshportsObscureHTML($port->maintainer);
				$HTML .= '?subject=FreeBSD%20Port:%20' . $port->category . '/' . $port->port . '" TITLE="email the FreeBSD Ports mailing list">';
				$HTML .= freshportsObscureHTML($port->maintainer) . '</A>';
			} else {
				$HTML .= '<b>';

				$HTML .= 'Maintainer:</b> <A HREF="' . MAILTO . ':' . freshportsObscureHTML($port->maintainer);
				$HTML .= '?subject=FreeBSD%20Port:%20' . $port->category . '/' . $port->port . '" TITLE="email the maintainer">';
				$HTML .= freshportsObscureHTML($port->maintainer) . '</A>';
			}

			$HTML .= ' ' . freshports_Search_Maintainer($port->maintainer) . '<br>';
		}

		// there are only a few places we want to show the last change.
		// therefore, we do not check ShowEverything here
		if ($this->ShowLastChange) {
			if ($port->updated != 0) {
				$HTML .= 'last change committed by ' . freshports_CommitterEmailLink($port->committer);  // separate lines in case committer is null

				$HTML .= ' ' . freshports_Search_Committer($port->committer);
 
				$HTML .= ' on <font size="-1">' . $port->updated . '</font>' . "\n";

				$HTML .= freshports_Email_Link($port->message_id);

				if ($port->EncodingLosses()) {
					$HTML .= ' ' . freshports_Encoding_Errors_Link();
				}

				$HTML .= ' ' . freshports_Commit_Link($port->message_id);
				$HTML .= ' ' . freshports_Commit_Link_Port($port->message_id, $port->category, $port->port);
				
				GLOBAL $freshports_CommitMsgMaxNumOfLinesToShow;

				$HTML .= freshports_PortDescriptionPrint($port->update_description, $port->encoding_losses, 
			 				$freshports_CommitMsgMaxNumOfLinesToShow, 
			 				freshports_MoreCommitMsgToShow($port->message_id,
	 				       $freshports_CommitMsgMaxNumOfLinesToShow));
			} else {
				$HTML .= "no changes recorded in FreshPorts<br>\n";
			}
		}

		# show the date added, if asked

		if ($this->ShowDateAdded || $this->ShowEverything) {
			$HTML .= '<b>Port Added:</b> <font size="-1">';
			if ($port->date_added) {
				$HTML .= FormatTime($port->date_added, 0, "Y-m-d H:i:s");
			} else {
				$HTML .= "unknown";
			}
			$HTML .= '</font><BR>' . "\n";
		}

		# show the date modified, if asked

		if ($this->ShowLastCommitDate || $this->ShowEverything) {
			$HTML .= '<b>Last Update:</b> <font size="-1">';
			if ($port->last_commit_date) {
				$HTML .= FormatTime($port->last_commit_date, 0, "Y-m-d H:i:s");
			} else {
				$HTML .= "unknown";
			}
			$HTML .= '</font><br>' . "\n";
			$HTML .= '<b>SVN Revision:</b> <font size="-1">';
			if ($port->svn_revision) {
				$HTML .= freshports_svnweb_ChangeSet_Link_Text($port->svn_revision, $port->svn_hostname, $port->path_to_repo);
			} else {
				$HTML .= "unknown";
			}
			$HTML .= '</font><BR>' . "\n";
		}

		$HTML .= PeopleWatchingThisPortAlsoWatch($this->db, $port->element_id);

		if ($port->categories) {
			// remove the primary category and remove any double spaces or trailing/leading spaces
			// this ensures that explode gives us the right stuff
			if (IsSet($port->category_looking_at)) {
				$CategoryToRemove = $port->category_looking_at;
			} else {
				$CategoryToRemove = $port->category;
			}
			$Categories = str_replace($CategoryToRemove, '', $port->categories);
			$Categories = str_replace('  ', ' ', $Categories);
			$Categories = trim($Categories);
			if ($Categories) {
				$HTML .= "<b>Also Listed In:</b> ";
				$CategoriesArray = explode(" ", $Categories);
				$Count = count($CategoriesArray);
				for ($i = 0; $i < $Count; $i++) {
					$Category = $CategoriesArray[$i];
#					$CategoryID = freshports_CategoryIDFromCategory($Category, $this->db);
#					if ($CategoryID) {
#						// this is a real category
						$HTML .= '<a href="/' . $Category . '/">' . $Category . '</a>';
#					} else {
#						$HTML .= $Category;
#					}
					if ($i < $Count - 1) {
						$HTML .= " ";
					}
				}
				$HTML .= "<br>\n";
			}
		}
		
		$HTML .= '<b>License:</b> ';
		if ($port->license) {
		        $HTML .= htmlentities($port->license);
		} else {
		        $HTML .= 'not specified in port';
		}

		$HTML .= "<br>\n";
    

		# The ad goes here
		if ($this->ShowAd || $this->ShowEverything) {
			$HTML .= port_display_AD;
		}

		if ($this->ShowDescriptionLong || $this->ShowEverything) {
			$HTML .= '<PRE CLASS="code">' . htmlify(_forDisplay($port->long_description)) . '</PRE>';
		}

		if ($this->ShowChangesLink || $this->ShowEverything) {
			$HTML .=  $this->link_to_repo();
		}

		if ($port->PackageExists() && ($this->ShowPackageLink || $this->ShowEverything)) {
			// package
			$HTML .= ' <b>:</b> ';
			$HTML .= '<A HREF="' . FRESHPORTS_FREEBSD_FTP_URL . '/' . freshports_PackageVersion($port->version, $port->revision, $port->epoch);
			$HTML .= '.tgz">Package</A>';
		}

		if ($port->homepage && ($this->ShowHomepageLink || $this->ShowEverything)) {
			$HTML .= ' <b>:</b> ';
			$HTML .= '<a HREF="' . _forDisplay($port->homepage) . '" TITLE="Homepage for this port">Homepage</a>';
		}

		if (defined('PORTSMONSHOW')  && ($this->ShowPortsMonLink || $this->ShowEverything)) {
			$HTML .= ' <b>:</b> ' . freshports_PortsMonitorURL($port->category, $port->port);
		}

		if (defined('CONFIGUREPLISTSHOW')  && ($this->ShowConfigurePlist || $this->ShowEverything)) {
			$HTML .= '<br>' . $this->ShowConfigurePlist();
		}

		$HTML .= '<b>Dependency line</b>: <span class="file">' . $port->package_name . '>0:' . $this->DisplayPlainText() . '</span><br>';

		# only show if we're meant to show, and if the port has not been deleted.
		if ($this->ShowPackageLink || $this->ShowEverything) {
			$HTML .= "\n<hr>\n";
			if ($port->IsDeleted()) {
				$HTML .= '<p>No installation instructions: this port has been deleted.</p>';
				$HTML .= '<p>The package name of this deleted port was: <code class="code">' . $port->latest_link . '</code></p>';
			} else {
				$HTML .= '<p><b>To install <a href="/faq.php#port" TITLE="what is a port?">the port</a>:</b> <code class="code">cd /usr/ports/'  . $port->category . '/' . $port->port . '/ && make install clean</code><br>';
				if (IsSet($port->no_package) && $port->no_package != '') {
					$HTML .= '<p><b>No <a href="/faq.php#package" TITLE="what is a package?">package</a> is available:</b> ' . $port->no_package . '</p>';
					} else {
					if ($port->forbidden || $port->broken || $port->ignore || $port->restricted || !$port->PackageIsAvailable()) {
						$HTML .= '<p><b>A <a href="/faq.php#package" TITLE="what is a package?">package</a> is not available for ports marked as: Forbidden / Broken / Ignore / Restricted</b></p>';
					} else {
						$HTML .= '<b>To add the <a href="/faq.php#package" TITLE="what is a package?">package</a>:</b> <code class="code">pkg install ' . $port->package_name . '</code></p>';
					}
				}
			}

			$HTML .= '<p><b>PKGNAME:</b> ';
			if ($port->PackageIsAvailable()) {
			  $HTML .= $port->package_name;
			} else {
			  $HTML .= 'there is no package for this port: <span class="file">' . $port->PackageNotAvailableReason() . '</span>';
			}
			$HTML .= '</p>';

			if ($port->only_for_archs) {
			  $HTML .= '<p><b>ONLY_FOR_ARCHS:</b> ';
			  $HTML .= htmlify($port->only_for_archs);
			  $HTML .= '</p>';
			}

			if ($port->not_for_archs) {
			  $HTML .= '<p><b>NOT_FOR_ARCHS:</b> ';
			  $HTML .= htmlify($port->not_for_archs);
			  $HTML .= '</p>';
			}

			if ($this->ShowEverything || $this->ShowDistInfo) {
				$HTML .= '<p><b>distinfo:</b>';

				if ($port->distinfo) {
					$HTML .= '<blockquote><pre>' . $port->distinfo . '</pre></blockquote>';
				} else {
					$HTML .= ' There is no distinfo for this port.';
				}
				$HTML .= '<p>';
			}
		}

		if ($this->ShowEverything || $this->ShowMasterSlave) {
			#
			# Display our master port
			#

			$MasterSlave = new MasterSlave($port->dbh);
			$NumRows = $MasterSlave->FetchByMaster($port->category . '/' . $port->port);

			if ($port->IsSlavePort() || $NumRows > 0) {
				$HTML .= "\n<hr>\n";
			}

			if ($port->IsSlavePort()) {
				$HTML .= '<dl><dt><b>Master port:</b> ';
				list($MyCategory, $MyPort) = explode('/', $port->master_port);
				$HTML .= freshports_link_to_port($MyCategory, $MyPort, $this->branch);
				$HTML .= "</dt>\n";
				$HTML .= "</dl>\n";
			}
	
			#
			# Display our slave ports
			#

			if ($NumRows > 0) {
				$HTML .= '<span class="slaveports">Slave ports</span>' . "\n" . '<ol class="slaveports" id="slaveports">';
				for ($i = 0; $i < $NumRows; $i++) {
					$MasterSlave->FetchNth($i);
					$HTML .= '<li>' . freshports_link_to_port($MasterSlave->slave_category_name, $MasterSlave->slave_port_name, $this->branch) . '</li>';
				}
				$HTML .= "</ol>\n";
			}
		}
	
		if ($this->ShowDepends || $this->ShowEverything) {
			if ($port->depends_build || $port->depends_run || $port->depends_lib) {
				$HTML .= '<hr><p><big>NOTE: FreshPorts displays only information on required and default dependencies.  Optional dependencies are not covered.</big></p>';
			}

			if ($port->depends_build) {
				$HTML .= '<span class="required">Build dependencies:</span>' . "\n" . '<ol class="required" id="requiredtobuild">';
				$HTML .= freshports_depends_links($this->db, $port->depends_build, $this->branch);
				$HTML .= "\n</ol>\n";
			}

			if ($port->depends_run) {
				$HTML .= '<span class="required">Runtime dependencies:</span>' . "\n" . '<ol class="required" id="requiredtorun">';
				$HTML .= freshports_depends_links($this->db, $port->depends_run, $this->branch);
				$HTML .= "\n</ol>\n";
			}

			if ($port->depends_lib) {
				$HTML .= '<span class="required">Library dependencies:</span>' . "\n" . '<ol class="required" id="requiredlibraries">';
				$HTML .= freshports_depends_links($this->db, $port->depends_lib, $this->branch);
				$HTML .= "\n</ol>\n";
			}

			if ($port->fetch_depends) {
				$HTML .= '<span class="required">Fetch dependencies:</span>' . "\n" . '<ol class="required" id="requiredfetches">';
				$HTML .= freshports_depends_links($this->db, $port->fetch_depends, $this->branch);
				$HTML .= "\n</ol>\n";
			}

			if ($port->patch_depends) {
				$HTML .= '<span class="required">Patch dependencies:</span>' . "\n" . '<ol class="required" id="requiredpatches">';
				$HTML .= freshports_depends_links($this->db, $port->patch_depends, $this->branch);
				$HTML .= "\n</ol>\n";
			}

			if ($port->extract_depends) {
				$HTML .= '<span class="required">Extract dependencies:</span>' . "\n" . '<ol class="required" id="requiredextracts">';
				$HTML .= freshports_depends_links($this->db, $port->extract_depends, $this->branch);
				$HTML .= "\n</ol>\n";
			}
			
			# XXX when adding new depends above, be sure to update the array in ShowDependencies()

			$HTML .= $this->ShowDependencies( $port );
		}

		if ($this->ShowDescriptionShort && ($this->ShowDescriptionLink || $this->ShowEverything)) {
			// Long description
			$HTML .= '<A HREF="/' . $port->category . '/' . $port->port .'/">Description</a>';

			$HTML .= ' <b>:</b> ';
		}
		
		if ($this->ShowEverything) {
			$HTML .= "\n<hr>\n";
			$HTML .= "<b>Configuration Options</b>\n<pre>";
			if ($port->showconfig) {
				$HTML .= $port->showconfig;
			} else {
				$HTML .= '     No options to configure';
			}
			$HTML .= "</pre>\n<hr>\n";
		}
		
		if ($this->ShowEverything && $port->uses) {
			$HTML .= "<b>USES:</b>\n<pre>";
			$HTML .= $port->uses;
			$HTML .= "</pre>\n<hr>\n";
		}

		# if there are conflicts
		if ($this->ShowEverything && ($port->conflicts || $port->conflicts_build || $port->conflicts_install)) {
			$HTML .= "<b>Conflicts:</b>\n<ul>";

			if ($port->conflicts) {
				$HTML .= "<li>CONFLICTS:";
				$HTML .= $this->htmlConflicts($port->conflicts);
				$HTML .= "\n</li>\n";
			}

			if ($port->conflicts_build) {
				$HTML .= "<li>CONFLICTS_BUILD:";
				$HTML .= $this->htmlConflicts($port->conflicts_build);
				$HTML .= "\n</li>\n";
			}

			if ($port->conflicts_install) {
				$HTML .= "<li>CONFLICTS_INSTALL:";
				$HTML .= $this->htmlConflicts($port->conflicts_install);
				$HTML .= "\n</li>\n";
			}

			$HTML .= "</ul>\n";

			$HTML .= "<b>Conflicts Matches:</b>\n<ul>";
			if (!empty($port->conflicts_matches)) {
				foreach($port->conflicts_matches as $match) {
					$HTML .= "<li>conflicts with " . freshports_link_to_port($match['category'], $match['port']) . '</li>';
				}
			} else {
				$HTML .= 'There are no matches for this port.  This is usually an error.';
				syslog(LOG_ERR, 'There are no matches for this port: ' . $port->element_pathname);
			}
			$HTML .= '</ul>';
		}

		if ($this->ShowEverything && $port->pkgmessage) {
			$HTML .= "<b>pkg-message:</b>\n<pre>";
			$HTML .= $port->pkgmessage;
			$HTML .= "</pre>\n<hr>\n";
		}

		if ($this->ShowEverything || $this->ShowMasterSites) {
			$HTML .= '<b>Master Sites:</b>' . "\n" . '<ol class="mastersites" id="mastersites">' . "\n";
			if (!empty($port->master_sites)) {

			  $MasterSites = explode(' ', $port->master_sites);
			  asort($MasterSites);
			  foreach ($MasterSites as $Site) {
				$HTML .= '<li>' . htmlify(_forDisplay($Site)) . "</li>\n";
			  }

			  $HTML .= "</ol>\n";
			} else {
			  $HTML .= 'There is no master site for this port.<br>';
			}

		}

#		$HTML .= "\n<hr>\n";

		$HTML .= "\n</DD>\n";
		$HTML .= "</DL>\n";

		return $HTML;
	}

	function LinkToPort() {
		$HTML = '<a href="/' . $this->port->category . '/' . $this->port->port . 
			            '/">' . $this->port->port . '</a>';

		return $HTML;
	}
	
	function ReplaceWatchListToken($OnWatchList, $HTML, $ElementID) {
		$Watch_HTML = '';
		
		if ($this->User && $this->User->id && ($this->ShowEverything || $this->ShowWatchListStatus)) {
			if ($OnWatchList) {
				$Watch_HTML .= freshports_Watch_Link_Remove($this->User->watch_list_add_remove, $OnWatchList, $ElementID);
			} else {
				$Watch_HTML .= freshports_Watch_Link_Add   ($this->User->watch_list_add_remove, $OnWatchList, $ElementID);
			}
		}
		
		$Watch_HTML .= ' ';
		
		$HTML = str_replace(port_display_WATCH_LIST_ADD_REMOVE, $Watch_HTML, $HTML);

		return $HTML;
	}

	function ReplaceAdvertismentToken($HTML, $Ad) {
		$HTML = str_replace(port_display_AD, $Ad, $HTML);

		return $HTML;
	}
	
	function ShowDependencies( $port ) {
		$HTML = '';

		$PortDependencies = new PortDependencies( $this->db );
		$Types = array( 'B' => 'Build', 'E' => 'Extract', 'F' => 'Fetch', 'L' => 'Libraries', 'P' => 'Patch', 'R' => 'Run' );
		foreach ( $Types as $type => $title ) {
			$NumRows = $PortDependencies->FetchInitialise( $port->id, $type );
			if ( $NumRows > 0 ) {
				// if this is our first output, put up our standard header
				if ( $HTML === '' ) {
					if ( $port->IsDeleted() ) {
						$HTML .= 'NOTE: dependencies for deleted ports are notoriously suspect<br>';
					}
					$HTML .= '<p class="required">This port is required by:</p>';
				}

				$HTML .= '<span class="required">for ' . $title . "</span>\n";
				$div = '<div id="RequiredBy' . $title . '">';
				$div .= "\n" . '<ol class="depends" id="requiredfor"' . $title . '>' . "\n";

				$deletedPortFound = true;
				for ( $i = 0; $i < $NumRows; $i++ ) {
					$PortDependencies->FetchNth($i);

					$div .= '<li>' . freshports_link_to_port_single( $PortDependencies->category, $PortDependencies->port, $this->branch );
					if ( $PortDependencies->status == 'D') {
						$div .= '<sup>*</sup>';
						$deletedPortFound = true;
					}
					$div .= "</li>\n";
					if ( $NumRows > DEPENDS_SUMMARY && $i == DEPENDS_SUMMARY  - 1) {
						$div .= '<a href="#" id="RequiredBy' . $title . 'Extra-show" class="showLink" onclick="showHide(\'RequiredBy' . $title . 'Extra\');return false;">Expand this list (' . $NumRows . ' items)</a>';
						$div .= '<span id="RequiredBy' . $title . 'Extra" class="more">';
					}
				}

				if ( $NumRows > DEPENDS_SUMMARY ) {
					$div .= '<a href="#" id="RequiredBy' . $title . 'Extra-hide" class="hideLink" onclick="showHide(\'RequiredBy' . $title . 'Extra\');return false;">Collapse this list.</a>';
					$div .= '</span>';
				}

				$div .= '</ol></div>';

				$HTML .= $div;
			}
		}

		if ( $HTML === '' ) {
			$HTML .= 'There are no ports dependent upon this port<br>';
		} elseif ($deletedPortFound) {
			$HTML .= '* - deleted ports are only shown under the <em>This port is required by</em> section.  It was harder to do for the <em>Required</em> section.  Perhaps later...';
		}

		return $HTML;
	}

	function ShowConfigurePlist() {
		$HTML = '';

		$ConfigurePlist = new PortConfigurePlist( $this->db );
		$NumRows = $ConfigurePlist->FetchInitialise( $this->port->id );
		if ( $NumRows > 0 ) {
			// if this is our first output, put up our standard header
			if ( $HTML === '' ) {
				$div = '<div id="ConfigurePlistDiv">';
				$div .= "\n" . '<ol class="configure" id="configureplist">Pseudo-<b>pkg-plist</b> information, but much better, from <code class="code">make generate-plist</code><br>' . "\n";

				$div .= '<a href="#" id="configureplist-Extra-show" class="showLink" onclick="showHide(\'configureplist-Extra\');return false;">Expand this list (' . $NumRows . ' items)</a>';
				$div .= '<br><span id="configureplist-Extra" class="more">';
				for ( $i = 0; $i < $NumRows; $i++ ) {
					$ConfigurePlist->FetchNth($i);

					$div .= '<li>' . $ConfigurePlist->installed_file . "</li>\n";
				}

				$div .= '<a href="#" id="configureplist-Extra-hide" class="hideLink" onclick="showHide(\'configureplist-Extra\');return false;">Collapse this list.</a>';
				$div .= '</span>';
				$div .= '</ol></div>';

				$HTML .= $div;
			}
		}

		if ( $HTML === '' ) {
			$HTML .= 'There is no configure plist information for this port<br>';
		}

		return $HTML;
	}

}
