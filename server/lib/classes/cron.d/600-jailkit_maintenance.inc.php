<?php

/*
Copyright (c) 2020, Jesse Norell <jesse@kci.net>
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

class cronjob_jailkit_maintenance extends cronjob {

	// job schedule
	protected $_schedule = '*/5 * * * *';
	protected $_run_at_new = true;

	//private $_tools = null;

	/* this function is optional if it contains no custom code */
	public function onPrepare() {
		global $app;

		parent::onPrepare();
	}

	/* this function is optional if it contains no custom code */
	public function onBeforeRun() {
		global $app;

		return parent::onBeforeRun();
	}

	public function onRunJob() {
		global $app, $conf;

		$app->uses('system,getconf');

		$server_config = $app->getconf->get_server_config($conf['server_id'], 'server');
		if(isset($server_config['migration_mode']) && $server_config['migration_mode'] == 'y') {
			//$app->log('Migration mode active, not running Jailkit updates.', LOGLEVEL_DEBUG);
			print "Migration mode active, not running Jailkit updates.\n";
		}

		$jailkit_config = $app->getconf->get_server_config($conf['server_id'], 'jailkit');
		if (isset($this->jailkit_config) && isset($this->jailkit_config['jailkit_hardlinks'])) {
			if ($this->jailkit_config['jailkit_hardlinks'] == 'yes') {
				$update_options = array( 'hardlink', );
			} elseif ($this->jailkit_config['jailkit_hardlinks'] == 'no') {
				$update_optiosn = array();
			}
		} else {
			$update_options = array( 'allow_hardlink', );
		}

		// limit the number of jails we update at one time according to time of day
		$num_jails_to_update = (date('H') < 6) ? 25 : 3;

		$sql = "SELECT domain_id, domain, document_root, jailkit_chroot_app_sections, jailkit_chroot_app_programs, delete_unused_jailkit FROM web_domain WHERE type = 'vhost' AND last_jailkit_update < (NOW() - INTERVAL 24 HOUR) AND server_id = ? ORDER by last_jailkit_update LIMIT ?";
		$records = $app->db->queryAllRecords($sql, $conf['server_id'], $num_jails_to_update);

		foreach($records as $rec) {
			if (!is_dir($rec['document_root']) || !is_dir($rec['document_root'].'/etc/jailkit')) {
				return;
			}

			//$app->log('Beginning jailkit maintenance for domain '.$rec['domain'].' at '.$rec['document_root'], LOGLEVEL_DEBUG);
			print 'Beginning jailkit maintenance for domain '.$rec['domain'].' at '.$rec['document_root']."\n";

			// check for any shell_user using this jail
			$shell_user_inuse = $app->db->queryOneRecord('SELECT shell_user_id FROM `shell_user` WHERE `parent_domain_id` = ? AND `chroot` = ? AND `server_id` = ?', $rec['domain_id'], 'jailkit', $conf['server_id']);

			// check for any cron job using this jail
			$cron_inuse = $app->db->queryOneRecord('SELECT id FROM `cron` WHERE `parent_domain_id` = ? AND `type` = ? AND `server_id` = ?', $rec['domain_id'], 'chrooted', $conf['server_id']);

			if ($shell_user_inuse || $cron_inuse || $rec['delete_unused_jailkit'] != 'y') {
				$sections = $jailkit_config['jailkit_chroot_app_sections'];
				if (isset($web['jailkit_chroot_app_sections']) && $web['jailkit_chroot_app_sections'] != '') {
					$sections = $web['jailkit_chroot_app_sections'];
				}
				$programs = $jailkit_config['jailkit_chroot_app_programs'];
				if (isset($web['jailkit_chroot_app_programs']) && $web['jailkit_chroot_app_programs'] != '') {
					$programs = $web['jailkit_chroot_app_programs'];
				}
				$app->system->update_jailkit_chroot($rec['document_root'], $sections, $programs, $update_options);
			} else {
				if ($rec['delete_unused_jailkit'] == 'y') {
					//$app->log('Removing unused jail: '.$rec['document_root'], LOGLEVEL_DEBUG);
					print 'Removing unused jail: '.$rec['document_root']."\n";
					$app->system->delete_jailkit_chroot($rec['document_root']);
				}
			}

			// might need to update master db here?  checking....
			$app->db->query("UPDATE `web_domain` SET `last_jailkit_update` = NOW() WHERE `document_root` = ?", $rec['document_root']);
		}

		parent::onRunJob();
	}

	/* this function is optional if it contains no custom code */
	public function onAfterRun() {
		global $app;

		parent::onAfterRun();
	}

}

