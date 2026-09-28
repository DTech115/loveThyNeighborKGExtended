<?php
/*
 * Copyright 2013 by Allen Tucker. 
 * This program is part of RMHC-Homebase, which is free software.  It comes with 
 * absolutely no warranty. You can redistribute and/or modify it under the terms 
 * of the GNU General Public License as published by the Free Software Foundation
 * (see <http://www.gnu.org/licenses/ for more information).
 * 
 */

/*
 * Created on Mar 28, 2008
 * @author Oliver Radwan <oradwan@bowdoin.edu>, Sam Roberts, Allen Tucker
 * @version 3/28/2008, revised 7/1/2015
 */


class Person {

	private $id; // (username)
	//private $start_date; // (dete of account creation)
	private $first_name;
	private $last_name;
	private $phone1;
	private $email;
	private $email_prefs;
	private $birthday;
	private $t_shirt_size;
	private $state;
	private $city;
	private $street_address;
	private $zip_code;
	private $emergency_contact_first_name;
	private $emergency_contact_phone;
	private $emergency_contact_relation;
	private $archived;
	private $password;
	private $contact_num;
	private $contact_method;
	private $type;
	private $status;
	private $photo_release;
	private $community_service;
	private $notes;

	function __construct(
        $id, $first_name, $last_name, $phone1, $email, $email_prefs, $birthday,
		$t_shirt_size, $state, $city, $street_address, $zip_code,
		$emergency_contact_first_name, $emergency_contact_phone, $emergency_contact_relation,
		$archived, $password, $contact_num, $contact_method, $type,
		$status, $photo_release, $community_service, $notes
		) {
        $this->id = $id;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->phone1 = $phone1;
        $this->email = $email;
        $this->email_prefs = $email_prefs;
        $this->birthday = $birthday;
        $this->t_shirt_size = $t_shirt_size;
        $this->state = $state;
        $this->city = $city;
        $this->street_address = $street_address;
        $this->zip_code = $zip_code;
        $this->emergency_contact_first_name = $emergency_contact_first_name;
        $this->emergency_contact_phone = $emergency_contact_phone;
        $this->emergency_contact_relation = $emergency_contact_relation;
        $this->archived = $archived;
        $this->password = $password;
        $this->contact_num = $contact_num;
        $this->contact_method = $contact_method;
        $this->type = $type;
        $this->status = $status;
        $this->photo_release = $photo_release;
        $this->community_service = $community_service;
        $this->notes = $notes;

        #$this->access_level = ($id == 'vmsroot') ? 3 : 1;

    }

    /*function get_is_new_volunteer() {
        return $this->is_new_volunteer;
    }

    function get_is_community_service_volunteer() {
        return $this->is_community_service_volunteer;
    }

    //YALDA DID THIS.
    function get_total_hours_volunteered() {
    	return $this->total_hours_volunteered;
   }*/

	function get_id() {
		return $this->id;
	}

	function get_first_name() {
		return $this->first_name;
	}

	function get_last_name() {
		return $this->last_name;
	}

	function get_phone1() {
		return $this->phone1;
	}

	
	function get_email() {
		return $this->email;
	}

	function get_email_prefs() {
		return $this->email_prefs;
	}

	function get_birthday() {
		return $this->birthday;
	}


	function get_t_shirt_size() {
		return $this->t_shirt_size;
	}

	function get_state() {
		return $this->state;
	}

	function get_city() {
		return $this->city;
	}

	function get_street_address() {
		return $this->street_address;
	}

	function get_zip_code() {
		return $this->zip_code;
	}

	function get_emergency_contact_first_name() {
		return $this->emergency_contact_first_name;
	}


	function get_emergency_contact_phone() {
		return $this->emergency_contact_phone;
	}


	function get_emergency_contact_relation() {
		return $this->emergency_contact_relation;
	}
    function get_archived() {
        return $this->archived;
    }

    function get_password() {
        return $this->password;
    }

    function get_contact_num() {
        return $this->contact_num;
    }

    function get_contact_method() {
        return $this->contact_method;
    }

	function get_type() {
		return $this->type;
	}

    function get_status() {
        return $this->status;
    }

    function get_photo_release() {
        return $this->photo_release;
    }

    function get_community_service() {
        return $this->community_service;
    }

	function get_notes() {
        return $this->notes;
    }

	function get_access_level() {
		if($this->id == 'vmsroot') {
			$access = 3;	
		return $access;
		} elseif($this->id == 'vmskiosk') {
			$access = 4;
			return $access;
		} elseif(preg_match("/admin/i", $this->type)){
			//the regex should catch anything resembling "admin" or "administrator" just in case people aren't consistent
			$access = 2;
			return $access;
		} else {
			$access = 1;
			return $access;
		}
	}
}