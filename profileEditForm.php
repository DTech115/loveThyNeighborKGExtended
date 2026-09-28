<?php
    require_once('domain/Person.php');
    require_once('database/dbPersons.php');
    require_once('include/output.php');

    // Required imports for Cleave JS to work
    echo('<script src="https://nosir.github.io/cleave.js/dist/cleave.min.js"></script>');
    echo('<script src="https://nosir.github.io/cleave.js/dist/cleave-phone.i18n.js"></script>');
    $args = sanitize($_GET);
    if ($_SESSION['access_level'] >= 2 && isset($args['id'])) {
        $id = $args['id'];
        $editingSelf = $id == $_SESSION['_id'];
        // Check to see if user is a lower-level manager here
    } else {
        $editingSelf = true;
        $id = $_SESSION['_id'];
    }
    if(isset($_POST['adminID'])){
        setUserType($_POST['adminID'], "Admin");
    }
    if(isset($_POST['unAdminID'])){
        setUserType($_POST['unAdminID'], "Volunteer");
    }

    $person = retrieve_person($id);
    if (!$person) {
        echo '<main class="signup-form"><p class="error-toast">That user does not exist.</p></main></body></html>';
        die();
    }

    $times = [
        '12:00 AM', '1:00 AM', '2:00 AM', '3:00 AM', '4:00 AM', '5:00 AM',
        '6:00 AM', '7:00 AM', '8:00 AM', '9:00 AM', '10:00 AM', '11:00 AM',
        '12:00 PM', '1:00 PM', '2:00 PM', '3:00 PM', '4:00 PM', '5:00 PM',
        '6:00 PM', '7:00 PM', '8:00 PM', '9:00 PM', '10:00 PM', '11:00 PM',
        '11:59 PM'
    ];
    $values = [
        "00:00", "01:00", "02:00", "03:00", "04:00", "05:00", 
        "06:00", "07:00", "08:00", "09:00", "10:00", "11:00", 
        "12:00", "13:00", "14:00", "15:00", "16:00", "17:00", 
        "18:00", "19:00", "20:00", "21:00", "22:00", "23:00",
        "23:59"
    ];
    
    function buildSelect($name, $disabled=false, $selected=null) {
        global $times;
        global $values;
        if ($disabled) {
            $select = '
                <select id="' . $name . '" name="' . $name . '" disabled>';
        } else {
            $select = '
                <select id="' . $name . '" name="' . $name . '">';
        }
        if (!$selected) {
            $select .= '<option disabled selected value>Select a time</option>';
        }
        $n = count($times);
        for ($i = 0; $i < $n; $i++) {
            $value = $values[$i];
            if ($selected == $value) {
                $select .= '
                    <option value="' . $values[$i] . '" selected>' . $times[$i] . '</option>';
            } else {
                $select .= '
                    <option value="' . $values[$i] . '">' . $times[$i] . '</option>';
            }
        }
        $select .= '</select>';
        return $select;
    }
?>
<main class="signup-form">
    <?php if (isset($updateSuccess)): ?>
        <?php if ($updateSuccess): ?>
            <div class="happy-toast">Profile updated successfully!</div>
        <?php else: ?>
            <div class="error-toast">An error occurred.</div>
        <?php endif ?>
    <?php endif ?>
    <?php if ($isAdmin): ?>
        <?php if (strtolower($id) == 'vmsroot') : ?>
            <div class="error-toast">The root user profile cannot be modified</div></main></body>
            <?php die() ?>
        <?php endif ?>
    <?php endif ?>
    <div class="sidebar-wrapper">
        <div class="sidebar">
            <div class="sidebar-item">
                <img src="images/settings.png"><h3> Edit Profile</h3>
            </div>
            <div class="sidebar-item">
                <a href="#login">
                    <img src="images/change-password.png"> Login Credentials
                </a>
            </div>
            <div class="sidebar-item">
                <a href="#personal-info">
                    <img src="images/view-profile.svg"> Personal Information
                </a>
            </div>
            <div class="sidebar-item">
                <a href="#contact-info">
                    <img src="images/phone.png"> Contact Information
                </a>
            </div>
            <div class="sidebar-item">
                <a href="#notifs">
                    <img src="images/inbox.svg"> Notification Preferences
                </a>
            </div>
        </div>
    </div>
    <div class="main-content-box">
    <?php if ($_SESSION['access_level'] >= 2): ?>
        <!-- promotion and demotion behaviors. Only available to root user and admins -->
        <?php if ($person->get_access_level() == 1): ?>
        <!-- admin wants to promote user -->
        <form class="admin-status" method="post">
            <input type="hidden" name="adminID" value="<?php echo $person->get_id()?>"/>
            <fieldset class="section-box">
                <h3 class="mt-2" id="login">Update Admin Status</h3>
                <label>You are seeing this action because you are an administrator. This section is not required to edit this user's profile.</label>
                <div class="blue-div"></div>
                <p>Press this button to promote this person a LTN volunteer network administrator.</p>
            <button type="submit" class="button danger" onclick="return confirm('Are you sure you want to make this user an admin?');">Make Administrator</button>
            </fieldset>
        </form>
        <?php elseif ($editingSelf && getAdminCount() > 1): ?>
        <!-- admin wants to demote self, but there needs to be at least one admin at all times-->
        <form class="admin-status" method="post">
            <input type="hidden" name="unAdminID" value="<?php echo $person->get_id()?>"/>
            <fieldset class="section-box">
                <h3 class="mt-2" id="login">Update Admin Status</h3>
                <label>You are seeing this action because you are an administrator. This section is not required to edit your profile.</label>
                <div class="blue-div"></div>
                <p>Press this button to demote yourself back to a volunteer. You will lose all access to admin tools and privileges.</p>
            <button type="submit" class="button danger" onclick="return confirm('Are you sure you want to remove your admin privileges?');">Demote Self</button>
            </fieldset>
        </form>
        <?php elseif ($_SESSION['access_level'] == 3 && $editingSelf != true && $person->get_access_level() == 2): ?>
        <!-- Root user can demote admins -->
        <form class="admin-status" method="post">
            <input type="hidden" name="unAdminID" value="<?php echo $person->get_id()?>"/>
            <fieldset class="section-box">
                <h3 class="mt-2" id="login">Update Admin Status</h3>
                <label>You are seeing this action because you are using the root user. This section is not required to edit this user's profile.</label>
                <div class="blue-div"></div>
                <p>Press this button to demote this user back to a volunteer. They will lose all access to admin tools and privileges.</p>
            <button type="submit" class="button danger" onclick="return confirm('Are you sure you want to remove this user's admin privileges?');">Demote User</button>
            </fieldset>
        </form>          
        <?php endif; ?>
    <?php endif; ?>
    <form class="signup-form" method="post">
	<div class="text-center">
          <h2 class="mb-8">Edit <?php if($person->get_access_level() >= 2) { echo "Admin"; } ?> Profile</h2>
            <div class="info-box">
              <p>An asterisk ( <em>*</em> ) indicates a required field.</p>
            </div>
	</div>
        <fieldset class="section-box">
            <h3 class="mt-2" id="login">Login Credentials</h3>
            <div class="blue-div"></div>
            <label>Username</label>
            <p><?php echo $person->get_id() ?></p>

            <label>Password</label>
                <a class="button-signup" href='changePassword.php' style="color: var(--button-font-color); font-weight: bold; width: 28%;">Change Password</a>
        </fieldset>

        <fieldset class="section-box">
            <h3 class="mt-2" id="personal-info">Personal Information</h3>
            <div class="blue-div"></div>
            <label for="first_name"><em>* </em>First Name</label>
            <input type="text" id="first_name" name="first_name" value="<?php echo hsc($person->get_first_name()); ?>" required placeholder="Enter your first name">

            <label for="last_name"><em>* </em>Last Name</label>
            <input type="text" id="last_name" name="last_name" value="<?php echo hsc($person->get_last_name()); ?>" required placeholder="Enter your last name">

            <!--<label for="birthday"><em>* </em>Date of Birth</label>
            <input type="date" id="birthday" name="birthday" value="<?php //echo hsc($person->get_birthday()); ?>" required placeholder="Choose your birthday" max="<?php echo date('Y-m-d'); ?>"> -->

            <label for="t-shirt_size"><em>* </em>T-Shirt Size</label>
            <select id="t-shirt_size" name="t-shirt_size" required>
                <option value="x_small" <?php if($person->get_t_shirt_size() == 'x_small') echo 'selected'; ?>>X-Small</option>
                <option value="small" <?php if($person->get_t_shirt_size() == 'small') echo 'selected'; ?>>Small</option>
                <option value="medium" <?php if($person->get_t_shirt_size() == 'medium') echo 'selected'; ?>>Medium</option>
                <option value="large" <?php if($person->get_t_shirt_size() == 'large') echo 'selected'; ?>>Large</option>
                <option value="x_large" <?php if($person->get_t_shirt_size() == 'x_large') echo 'selected'; ?>>X-Large</option>
                <option value="xx_large" <?php if($person->get_t_shirt_size() == 'xx_large') echo 'selected'; ?>>XX-Large</option>
                <option value="xxx_large" <?php if($person->get_t_shirt_size() == 'xxx_large') echo 'selected'; ?>>XXX-Large</option>
            </select>  

            <label for="street_address"><em>* </em>Street Address</label>
            <input type="text" id="street_address" name="street_address" value="<?php echo hsc($person->get_street_address()); ?>" required placeholder="Enter your street address">

            <label for="city"><em>* </em>City</label>
            <input type="text" id="city" name="city" value="<?php echo hsc($person->get_city()); ?>" required placeholder="Enter your city">

            <label for="state"><em>* </em>State</label>
            <select id="state" name="state" required>
                <?php
                    $state = $person->get_state();
                    $states = array(
                        'Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut', 'Delaware', 'District Of Columbia', 'Florida', 'Georgia', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky', 'Louisiana', 'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota', 'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire', 'New Jersey', 'New Mexico', 'New York', 'North Carolina', 'North Dakota', 'Ohio', 'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island', 'South Carolina', 'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia', 'Washington', 'West Virginia', 'Wisconsin', 'Wyoming'
                    );
                    $abbrevs = array(
                        'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'DC', 'FL', 'GA', 'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY'
                    );
                    $length = count($states);
                    for ($i = 0; $i < $length; $i++) {
                        if ($abbrevs[$i] == $state) {
                            echo '<option value="' . $abbrevs[$i] . '" selected>' . $states[$i] . '</option>';
                        } else {
                            echo '<option value="' . $abbrevs[$i] . '">' . $states[$i] . '</option>';
                        }
                    }
                ?>
            </select>

            <label for="zip_code"><em>* </em>Zip Code</label>
            <input type="text" id="zip_code" name="zip_code" value="<?php echo hsc($person->get_zip_code()); ?>" pattern="[0-9]{5}" title="5-digit zip code" required placeholder="Enter your 5-digit zip code">
            
            <!-- <div class="median-div"></div>
            <label for="affiliation"><em>* </em>Military Affiliation</label>
            <?php //echo hsc($person->get_affiliation()); ?> -->
            </select>

            <!-- <label for="branch"><em>* </em>Branch of Service</label>
            <?php //echo hsc($person->get_branch()); ?> -->
        </fieldset>

        <fieldset class="section-box">
            <h3 class="mt-2" id="contact-info">Contact Information</h3>
            <div class="blue-div"></div>
            <label for="email"><em>* </em>E-mail</label>
            <input type="email" id="email" name="email" value="<?php echo hsc($person->get_email()); ?>" required placeholder="Enter your e-mail address">

            <label for="phone1">Phone Number</label>
            <input type="tel" id="phone1" class="phone" name="phone1" value="<?php echo formatPhoneNumber($person->get_phone1()); ?>" pattern="(\D{0,1})\d{3}(\D{0,2})\d{3}(.{0,1})\d{4}" placeholder="Ex. (555) 555-5555">

            <!--<label><em>* </em>Phone Type</label>
            <div class="radio-group">
                <?php //$type = $person->get_phone1type(); ?>
                <input type="radio" id="phone-type-cellphone" name="phone1type" value="cellphone" <?php //if ($type == 'cellphone') echo 'checked'; ?> required><label for="phone-type-cellphone">Cell</label>
                <input type="radio" id="phone-type-home" name="phone1type" value="home" <?php //if ($type == 'home') echo 'checked'; ?> required><label for="phone-type-home">Home</label>
                <input type="radio" id="phone-type-work" name="phone1type" value="work" <?php //if ($type == 'work') echo 'checked'; ?> required><label for="phone-type-work">Work</label>
            </div>-->

        </fieldset>

        <fieldset class="section-box">
            <h3 class="mt-2">Emergency Contact</h3>
            <div class="blue-div"></div>

            <p>Please provide us with someone to contact on your behalf in case of an emergency.</p>
            <label for="emergency_contact_first_name" required><em>* </em>First Name</label>
            <input type="text" id="emergency_contact_first_name" name="emergency_contact_first_name" value="<?php echo hsc($person->get_emergency_contact_first_name()); ?>" placeholder="Enter emergency contact name">

            <!-- <label for="emergency_contact_last_name" required>Last Name</label>
            <input type="text" id="emergency_contact_last_name" name="emergency_contact_last_name" value="<?php //echo hsc($person->get_emergency_contact_last_name()); ?>" placeholder="Enter emergency contact name"> -->

            <label for="emergency_contact_relation"><em>* </em>Contact Relation to You</label>
            <input type="text" id="emergency_contact_relation" name="emergency_contact_relation" value="<?php echo hsc($person->get_emergency_contact_relation()); ?>" placeholder="Ex. Spouse, Mother, Father, Sister, Brother, Friend">

            <label for="emergency_contact_phone"><em>* </em>Phone Number</label>
            <input type="tel" id="emergency_contact_phone" class="phone" name="emergency_contact_phone" value="<?php echo formatPhoneNumber($person->get_emergency_contact_phone()); ?>" pattern="(\D{0,1})\d{3}(\D{0,2})\d{3}(.{0,1})\d{4}" placeholder="Ex. (555) 555-5555">

            <!-- <label>Phone Type</label>
            <div class="radio-group">
                <?php //$type = $person->get_emergency_contact_phone_type(); ?>
                <input type="radio" id="phone-type-cellphone" name="emergency_contact_phone_type" value="cellphone" <?php //if ($type == 'cellphone') echo 'checked'; ?> ><label for="phone-type-cellphone">Cell</label>
                <input type="radio" id="phone-type-home" name="emergency_contact_phone_type" value="home" <?php //if ($type == 'home') echo 'checked'; ?> ><label for="phone-type-home">Home</label>
                <input type="radio" id="phone-type-work" name="emergency_contact_phone_type" value="work" <?php //if ($type == 'work') echo 'checked'; ?> ><label for="phone-type-work">Work</label>
            </div> -->
        
        </fieldset>

        <!--<fieldset class="section-box">
            <h3 class="mt-2">Volunteer Information</h3>
            <div class="blue-div"></div>

 
    <label>Account Type</label>
    <p>
        <?php 
            //echo $person->get_is_community_service_volunteer() 
                // ? 'Community Service Volunteer' 
                // : 'Standard Volunteer'; 
        ?>
    </p>
</fieldset>-->

        <!-- may be entirely useless? change to notifications? -->
        <fieldset class="section-box">
            <h3 class="mt-2" id="notifs">Notification Preferences</h3>
            <div class="blue-div"></div>

            <label>Email Preferences</label>
            <p>Would you like to recieve emails from Love Thy Neighbor Food Pantry?</p>
            <div class="radio-group">
                <div class="radio-element">
                    <input type="checkbox" id="reminders" name="email_prefs" value="reminders" <?php
                    if($person->get_email_prefs() == 'true') { echo 'checked'; } ?> >
                    <label for="reminders">Yes, send me emails</label>
                </div>
            </div>

            <!--<label>Are there any specific skills you have that you believe could be useful for volunteering at FredSPCA?</label>
            <input type="text" id="skills" name="skills" value="<?php //echo hsc($person->get_skills()); ?>" placeholder="">

            <label>Do you have any interests?</label>
            <input type="text" id="interests" name="interests" value="<?php //echo hsc($person->get_interests()); ?>" placeholder="">-->

            
        </fieldset>


        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="submit" name="profile-edit-form" value="Update Profile">
        <?php if ($editingSelf): ?>
            <a class="button cancel" href="viewProfile.php" style="margin-top: -.5rem">Cancel</a>
        <?php else: ?>
            <a class="button cancel" href="viewProfile.php?id=<?php echo htmlspecialchars($_GET['id']) ?>" style="margin-top: -.5rem">Cancel</a>
        <?php endif ?>
    </form>
    </div>
    <script>
        // Initialize Cleave.js for primary phone number
        new Cleave('#phone1', {
            phone: true,
            phoneRegionCode: 'US',
            delimiter: '-',
            numericOnly: true,
        });

        // Initialize Cleave.js for emergency contact phone number
        /*new Cleave('#emergency_contact_phone', {
            phone: true,
            phoneRegionCode: 'US',
            delimiter: '-',
            numericOnly: true,
        });*/
    </script>
</main>
