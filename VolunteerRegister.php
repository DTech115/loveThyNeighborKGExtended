<?php
    require_once('include/input-validation.php');
?>

<!DOCTYPE html>
<html>
<head>
    <?php require_once('database/dbMessages.php'); ?>
    <title>Love Thy Neighbor | Register</title>
    <link href="css/base.css" rel="stylesheet">
<!-- BANDAID FIX FOR HEADER BEING WEIRD -->
<!--<?php
//$tailwind_mode = true;
//require_once('header.php');
?>
<style>
    .date-box {
        background: #C9AB81;
        padding: 7px 30px;
        border-radius: 50px;
        box-shadow: -4px 4px 4px rgba(0, 0, 0, 0.25) inset;
        color: white;
        font-size: 24px;
        font-weight: 700;
        text-align: center;
    }
    .dropdown {
        padding-right: 50px;
    }
</style>-->
<!-- BANDAID END, REMOVE ONCE SOME GENIUS FIXES -->
</head>
<body class="relative">
<?php
    require_once('domain/Person.php');
    require_once('database/dbPersons.php');

    $showPopup = false;

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $ignoreList = array('password', 'password-reenter');
        $args = sanitize($_POST, $ignoreList);

        //  id, first_name, last_name, phone_number, email, email_prefs,
        //  birthday, t-shirt_size, state, city, street_address, zip_code,
        //  emergency_contact_first_name, emergency_contact_phone,
        //  emergency_contact_relation, archived, password, contact_num,
        //  contact_method, type, status, photo_release, community_service,
        //  notes

        // Love Thy Neighbor KG required
        $required = array (
            'first_name', 'last_name', 'phone1', 'email',
            'birthday', 't_shirt_size', 'state', 'city', 'street_address', 'zip',
            'emergency_contact_first_name', 'emergency_contact_phone', 'emergency_contact_relation',
            'password', 'privacy_consent', 'photo_release'
        );

        $optional = array(
            'email_prefs', 'notes'
        );

        // contact_num, contact_method are not needed at all

        $errors = false;

        if (!wereRequiredFieldsSubmitted($args, $required)) {
            $errors = true;
        }

        $first_name = $args['first_name'];
        $last_name = $args['last_name'];
        $t_shirt_size = $args['t_shirt_size'];
        //$age = $args['age']; // Passes either "true" or "false" 
        $birthday = validateDate($args['birthday']);
        if (!$birthday) {
            echo "<p>Invalid birthdate.</p>";
            $errors = true;
        } 

        $street_address = $args['street_address'];
        $city = $args['city'];
        $state = $args['state'];
        if (!valueConstrainedTo($state, array(
            'AK','AL','AR','AZ','CA','CO','CT','DC','DE','FL','GA','HI','IA','ID','IL','IN','KS','KY','LA','MA','MD','ME',
            'MI','MN','MO','MS','MT','NC','ND','NE','NH','NJ','NM','NV','NY','OH','OK','OR','PA','RI','SC','SD','TN','TX',
            'UT','VA','VT','WA','WI','WV','WY'))) {
            echo "<p>Invalid state.</p>";
            $errors = true;
        }

        $zip_code = $args['zip'];
        if (!validateZipcode($zip_code)) {
            echo "<p>Invalid ZIP code.</p>";
            $errors = true;
        }

        $email = strtolower($args['email']);
        if (!validateEmail($email)) {
            echo "<p>Invalid email.</p>";
            $errors = true;
        }

        if(isset($args['phone1'])) { 
            $phone1 = validateAndFilterPhoneNumber($args['phone1']);
            if (!$phone1) {
                echo "<p>Invalid phone number.</p>";
                $errors = true;
            }
        } else {
            $phone1 = null;
        }

        if(isset($args['email_prefs'])) {
            $email_consent = $args['email_prefs'];
        } else {
            $email_consent = 'false';
        }

        if(!isset($args['privacy_consent']) || $args['privacy_consent'] == 'no') {
            echo "<p>You must agree to the privacy policy to create an account.</p>";
            $errors = true;
        }

        // if(!isset($args['photo_release']) || $args['photo_release'] == 'no') {
        //     echo "<p>You must agree to being photographed to create an account.</p>";
        //     $errors = true;
        // }

        //$affiliation = $args['affiliation']; - Brooke wants to Delete (whiskey)
        //$branch = $args['branch'];   -Brooke wants to Delete (whiskey)

        $emergency_contact_first_name = $args['emergency_contact_first_name'];   //It is actually the first and last name -Brooke
        //$emergency_contact_last_name = $args['emergency_contact_last_name'];
        $emergency_contact_relation = $args['emergency_contact_relation'];

        $emergency_contact_phone = validateAndFilterPhoneNumber($args['emergency_contact_phone']);
        if (!$emergency_contact_phone) {
            echo "<p>Invalid emergency contact phone.</p>";
            $errors = true;
        }

        $type = "Volunteer";
        $archived = 0;
        $status = "Active";
        $is_community_service_volunteer = $args['is_community_service_volunteer'] === 'yes' ? 1 : 0;
        $photo_release = $args['photo_release'] === 'yes' ? 1 : 0;
        $contact_num = null;
        $contact_method = null;
        //$notes = $args['notes'];
        $notes = null;

        $id = $args['username'];

        $password = isSecurePassword($args['password']);
        if (!$password) {
            echo "<p>Password is not secure enough.</p>";
            $errors = true;
        } else {
            $password = password_hash($args['password'], PASSWORD_BCRYPT);
        }

        if ($errors) {
            echo '<p class="error">Your form submission contained unexpected or invalid input.</p>';
            die();
        }

        // Love Thy Neighbor KG newperson
        $newperson = new Person(
            $id, $first_name, $last_name,
            $phone1, $email, $email_consent,
            $birthday, $t_shirt_size, $state,
            $city, $street_address, $zip_code,
            $emergency_contact_first_name, 
            $emergency_contact_phone, $emergency_contact_relation,
            $archived, $password, $contact_num, $contact_method,
            $type, $status, $photo_release, $is_community_service_volunteer, $notes
        );

        $result = add_person($newperson);
        if (!$result) {
            $showPopup = true;
        } else {
            echo '<script>document.location = "login.php?registerSuccess";</script>';
            $title = $id . " has been added as a volunteer";
            $body = "New volunteer account has been created";
            system_message_all_admins($title, $body);
        }
    } else {
        require_once('registrationForm.php');
    }
?>

<?php if ($showPopup): ?>
<div id="popupMessage" class="absolute left-[40%] top-[20%] z-50 bg-red-800 p-4 text-white rounded-xl text-xl shadow-lg">
    That username is already taken.
</div>
<?php endif; ?>

<!-- Auto-hide popup -->
<script>
window.addEventListener('DOMContentLoaded', () => {
    const popup = document.getElementById('popupMessage');
    if (popup) {
        popup.style.transition = 'opacity 0.5s ease';
        setTimeout(() => {
            popup.style.opacity = '0';
            setTimeout(() => {
                popup.style.display = 'none';
            }, 500);
        }, 4000);
    }
});
</script>

</body>
</html>
