<?php
// IF YOU HAVE NOT DONE SO, PLEASE READ THE README FILE FOR DIRECTIONS!!!

/**
 * OpenID-LDAP-PHP
 * An open source PHP-based OpenID IdP package using LDAP as backend.
 *
 * By Zdravko Stoychev <zdravko (at) 5group (dot) com> aka Dako.
 * Copyright 1996-2008 by 5Group & Co. http://www.5group.com/
 * See LICENSE file for more details.
 */

/**
 * LDAP connection settings
 * @name $ldap
 * @global array $GLOBALS['ldap']
 */
$GLOBALS['ldap'] = array (
	# Connection settings
	'primary'		=> '10.0.0.111',
	'fallback'		=> '10.0.0.222',
	'binddn'		=> 'cn=<name>,cn=users,dc=domain,dc=local',
	'password'		=> '<pass>',
	'searchdn'		=> 'cn=users,dc=domain,dc=local',
	'filter'		=> '(&(cn=%s)(mail=*))',
	'testdn'		=> 'cn=%s,cn=users,dc=domain,dc=local',

	# SREG names matching to LDAP attribute names
	'nickname'		=> 'uid',
	'email'			=> 'mail',
	'fullname'		=> array('givenName', 'sn'),
#	'dob'			=> '',
#	'postcode'		=> '',
#	'language'		=> '',
#	'timezone'		=> '',
#	'gender'		=> '',
	'country'		=> 'c'
);


/**
 * Search for LDAP account by username. Populate $sreg if found
 * string $username
 */
function find_ldap ($username) {
	global $sreg, $ldap, $profile;

        $no = "no";
        $profile['user_found'] = false;

        if ($username != "") {
                $ds = ldap_connect($ldap['primary']) or $ds = ldap_connect($ldap['fallback']);
                if ($ds) {
                        $r = ldap_bind($ds,$ldap['binddn'],$ldap['password']);
                        $sr = ldap_search($ds,$ldap['searchdn'],sprintf($ldap['filter'],$username));
                        $info = ldap_get_entries($ds, $sr);

                        if ($info["count"] == 1) {
                                $no = "ok";
                                $profile['user_found'] = true;

				# Populate user information from LDAP - if (array_key_exists('keyname', $ldap))...
				$sreg['nickname'] = $info[0][$ldap['nickname']][0];
				$sreg['email']    = $info[0][$ldap['email']][0];

                                $values = is_array($ldap['fullname']) ? $ldap['fullname'] : array($ldap['fullname']);
                                $fullname = '';
	                        foreach ($values as $vname) {
				        $aname = $info[0][$vname][0];
				        if ($aname != '') $fullname = ($fullname == '' ? $aname : $fullname . ' ' . $aname);
                                }
                                $sreg['fullname'] = $fullname;

				$sreg['country']  = $info[0][$ldap['country']][0];

				# Values not obtained from LDAP
				$sreg['language'] = 'en';
				$sreg['postcode'] = '1000';
				$sreg['timezone'] = 'Europe/Sofia';
                        }
                        ldap_close($ds);
                }
        }
        return $no;
}


/**
 * Perform LDAP bind test with provided username and password
 * string $username, string $password
 */
function test_ldap ($username, $password) {
	global $ldap;

        $no = "no";
        if ($username != "") {
                $ds = ldap_connect($ldap['primary']) or $ds = ldap_connect($ldap['fallback']);
                if ($ds) {
                        if (ldap_bind($ds,sprintf($ldap['testdn'],$username),$password)) $no = "ok";
                        ldap_close($ds);
                }
        }
        return $no;
}

/* notepad here:
  ... This was acheived with this stanza in slapd.conf

  access to attr=userPassword
        by self write
        by anonymous auth
        by * none

  print "<p>Change password ";
  if (ldap_mod_replace ($ldapconn, "uid=".$username.",dc=example,dc=com", 
	array('userpassword' => "{MD5}".base64_encode(pack("H*",md5($newpass))) { 
	print "succeded"; } else { print "failed"; }
  print ".</p>\n";
*/

?>
