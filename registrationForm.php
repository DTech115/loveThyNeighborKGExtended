<!-- imports -->
<script src="https://nosir.github.io/cleave.js/dist/cleave.min.js"></script>
<script src="https://nosir.github.io/cleave.js/dist/cleave-phone.i18n.js"></script>
<!-- Hero Section with Title -->
<header class="hero-header"> 
    <div class="center-header">
        <h1 style="color: #004AAD; font-weight: bold;">Account Registration</h1>
    </div>
</header>

<main>
  <div class="main-content-box" style="color: #004AAD; font-weight: bold;">
    <form class="signup-form" method="post" style="color: #004AAD; font-weight: bold;">
	<div class="text-center spacing-bottom" style="color: #004AAD; font-weight: bold;">
          <h2 class="mb-8">Registration Form</h2>
            <div class="info-box">
              <p class="sub-text">Please fill out each section of the following form to create your account.</p>
              <p>An asterisk ( <em>*</em> ) indicates a required field.</p>
            </div>
	</div>
        
        <fieldset class="section-box mb-4">

            <h3 class="mt-2">Personal Information</h3>
            <p class="mb-2">The following information will help us identify you within our system.</p>
	    <div class="blue-div"></div>

            <label for="first_name"><em>* </em>First Name</label>
            <input type="text" id="first_name" name="first_name" required placeholder="Enter your first name">

            <label for="last_name"><em>* </em>Last Name</label>
            <input type="text" id="last_name" name="last_name" required placeholder="Enter your last name">


            <div class="median-div"></div>

            <label for="birthday"><em>* </em>Date of Birth</label>
            <input type="date" id="birthday" name="birthday" required placeholder="Enter your birthday" max="<?php echo date('Y-m-d'); ?>">
            

            <label for="t_shirt_size"><em>* </em>T-Shirt Size</label> <!-- Cahnge to be a drop down -->
            <!--<input type="text" id="t-shirt_size" name="t-shirt_size" required placeholder="Enter your t-shirt size"> -->
            <select id="t_shirt_size" name="t_shirt_size" required>
                <option value="x_small">X-Small</option>
                <option value="small">Small</option>
                <option value="medium">Medium</option>
                <option value="large">Large</option>
                <option value="x_large">X-Large</option>
                <option value="xx_large">XX-Large</option>
                <option value="xxx_large">XXX-Large</option>
            </select>

            <!--<label><em>* </em>Are you 21 or older?</label>
            <div class="radio-group">
                <div class="radio-element">
                    <input type="radio" id="yes" name="age" value="true" required>
                    <label for="yes">Yes</label>
                </div>
                <div class="radio-element">
                    <input type="radio" id="no" name="age" value="false">
                    <label for="no">No</label>
                </div>
            </div>
            <div class="median-div"></div> -->

            <div class="median-div"></div>
            
            <label for="street_address"><em>* </em>Street Address</label>
            <input type="text" id="street_address" name="street_address" required placeholder="Enter your street address">

            <label for="city"><em>* </em>City</label>
            <input type="text" id="city" name="city" required placeholder="Enter your city">

            <label for="state"><em>* </em>State</label>
            
            <select id="state" name="state" required>
                <option value="AL">Alabama</option>
                <option value="AK">Alaska</option>
                <option value="AZ">Arizona</option>
                <option value="AR">Arkansas</option>
                <option value="CA">California</option>
                <option value="CO">Colorado</option>
                <option value="CT">Connecticut</option>
                <option value="DE">Delaware</option>
                <option value="DC">District Of Columbia</option>
                <option value="FL">Florida</option>
                <option value="GA">Georgia</option>
                <option value="HI">Hawaii</option>
                <option value="ID">Idaho</option>
                <option value="IL">Illinois</option>
                <option value="IN">Indiana</option>
                <option value="IA">Iowa</option>
                <option value="KS">Kansas</option>
                <option value="KY">Kentucky</option>
                <option value="LA">Louisiana</option>
                <option value="ME">Maine</option>
                <option value="MD">Maryland</option>
                <option value="MA">Massachusetts</option>
                <option value="MI">Michigan</option>
                <option value="MN">Minnesota</option>
                <option value="MS">Mississippi</option>
                <option value="MO">Missouri</option>
                <option value="MT">Montana</option>
                <option value="NE">Nebraska</option>
                <option value="NV">Nevada</option>
                <option value="NH">New Hampshire</option>
                <option value="NJ">New Jersey</option>
                <option value="NM">New Mexico</option>
                <option value="NY">New York</option>
                <option value="NC">North Carolina</option>
                <option value="ND">North Dakota</option>
                <option value="OH">Ohio</option>
                <option value="OK">Oklahoma</option>
                <option value="OR">Oregon</option>
                <option value="PA">Pennsylvania</option>
                <option value="RI">Rhode Island</option>
                <option value="SC">South Carolina</option>
                <option value="SD">South Dakota</option>
                <option value="TN">Tennessee</option>
                <option value="TX">Texas</option>
                <option value="UT">Utah</option>
                <option value="VT">Vermont</option>
                <option value="VA" selected>Virginia</option>
                <option value="WA">Washington</option>
                <option value="WV">West Virginia</option>
                <option value="WI">Wisconsin</option>
                <option value="WY">Wyoming</option>
            </select>

            <label for="zip"><em>* </em>Zip Code</label>
            <input type="text" id="zip" name="zip" pattern="[0-9]{5}" title="5-digit zip code" required placeholder="Enter your 5-digit zip code">
        </fieldset>

        <fieldset class="section-box mb-4">
            <h3>Contact Information</h3>
            <p class="mb-2">The following information will help us determine the best way to contact you regarding event coordination.</p>
	    <div class="blue-div"></div>

            <label for="email"><em>* </em>E-mail</label>
            <input type="email" id="email" name="email" required placeholder="Enter your e-mail address">

            <label for="email_consent">E-mail Notifications</label>
            <p>By checking the box below, you consent to recieve emails from the Love Thy Neighbor Community Food Pantry in King George, Virginia. You may change this at any time.</p>
            <label><input type="checkbox" id="email_prefs" name="email_prefs" value="true"> I consent.</label>

            <div class="median-div"></div>

            <label for="phone1"><em>* </em>Phone Number</label>
            <input type="tel" id="phone1" class="phone" name="phone1" pattern="(\D{0,1})\d{3}(\D{0,2})\d{3}(.{0,1})\d{4}" required placeholder="Ex. 555-555-5555">

        </fieldset>

        <fieldset class="section-box mb-4">
            <h3>Emergency Contact</h3>
            <p class="mb-2">Please provide us with someone to contact on your behalf in case of an emergency.</p>
	    <div class="blue-div"></div>

            <label for="emergency_contact_first_name" required><em>* </em>Contact First Name and Last Name</label>
            <input type="text" id="emergency_contact_first_name" name="emergency_contact_first_name" required placeholder="Enter emergency contact first name and last name">

            <!--<label for="emergency_contact_last_name" required><em>* </em>Contact Last Name</label>
            <input type="text" id="emergency_contact_last_name" name="emergency_contact_last_name" required placeholder="Enter emergency contact last name"> -->

            <label for="emergency_contact_relation"><em>* </em>Contact Relation to You</label>
            <input type="text" id="emergency_contact_relation" name="emergency_contact_relation" required placeholder="Ex. Spouse, Mother, Father, Sister, Brother, Friend">

            <label for="emergency_contact_phone"><em>* </em>Contact Phone Number</label>
            <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" pattern="(\D{0,1})\d{3}(\D{0,2})\d{3}(.{0,1})\d{4}" required placeholder="Enter emergency contact phone number. Ex. 555-555-5555">
        </fieldset>

        <script>
        // Initialize Cleave.js for primary phone number
        new Cleave('#emergency_contact_phone', {
            phone: true,
            phoneRegionCode: 'US',
            delimiter: '-',
            numericOnly: true,
        });
        </script>

        <script>
        // Initialize Cleave.js for primary phone number
        new Cleave('#phone1', {
            phone: true,
            phoneRegionCode: 'US',
            delimiter: '-',
            numericOnly: true,
        });
        </script>


        <fieldset class="section-box mb-4">
            <h3>Login Credentials</h3>
            <p class="mb-2">You will use the following information to log in to the system.</p>
	    <div class="blue-div"></div>

            <label for="username"><em>* </em>Username</label>
            <input type="text" id="username" name="username" required placeholder="Enter a username">

            <label for="password"><em>* </em>Password</label>
            <p>Your password must be at least 8 characters long, contain at least one number, one uppercase letter, and one lowercase letter.</p>
            <input type="password" id="password" name="password" placeholder="Enter a strong password" required>
            <p id="password-error" class="error hidden">Password does not meet requirements.</p>

            <label for="password-reenter"><em>* </em>Re-enter Password</label>
            <input type="password" id="password-reenter" name="password-reenter" placeholder="Re-enter password" required>
            <p id="password-match-error" class="error hidden">Passwords do not match.</p>
            
              <!-- Required by backend -->
        <!--<input type="hidden" name="is_new_volunteer" value="1">
        <input type="hidden" name="total_hours_volunteered" value="0"> -->
        </fieldset>

        <fieldset class="section-box mb-4">
            <h3>Volunteer Information</h3>
            <p class="mb-2">Are you volunteering to complete required community service (school, court, etc.)?</p>
        <div class="blue-div"></div>
            <label><em>* </em> Community Service Requirement Confirmation</label>
            <p>Please indicate whether your volunteer participation is to fufill a required community service obligation.</p>
            <div class="radio-group">
                <div class="radio-element">
                    <input type="radio" id="agree" name="is_community_service_volunteer" value="yes" required>
                    <label for="agree">Yes, I am completing required community service</label>
                </div>
                <div class="radio-element">
                    <input type="radio" id="disagree" name="is_community_service_volunteer" value="no" required>
                    <label for="disagree">No, I am volunteering by choice</label>
                </div> 
            </div>
        
        </fieldset>
        
        <fieldset class="section-box mb-4">
            <h3>Consent Notice</h3>
            <p class="mb-2">Please review the following before creating your account.</p>
        <div class="blue-div"></div>
            <label><em>* </em> Privacy Policy</label>
            <p>I confirm that I have read the <u><a href="https://www.kgfood.org/privacy">Privacy Policy</a> </u>and consent to the Love Thy Neighbor Community Food Pantry in King George, Virginia, collecting and storing my information for the purposes outlined therein.</p>
            <div class="radio-group">
                <div class="radio-element">
                    <input type="radio" id="agree" name="privacy_consent" value="yes" required>
                    <label for="agree">Yes</label>
                </div>
                <!--<div class="radio-element">
                    <input type="radio" id="disagree" name="privacy_consent" value="no">
                    <label for="disagree">I do not agree.</label>
                </div>-->
            </div>
            <br>
            <label><em>* </em> Picture Policy</label>
            <p>I consent to being photographed and authorize the Love Thy Neighbor Community Food Pantry in King George, Virginia, taking and using my image.</p>
            <div class="radio-group">
                <div class="radio-element">
                    <input type="radio" id="agree" name="photo_release" value="yes" required>
                    <label for="agree">Yes</label>
                </div>
                <div class="radio-element">
                    <input type="radio" id="disagree" name="photo_release" value="no">
                    <label for="disagree">No</label>
                </div>
            </div>   
        </fieldset>
        <p class="text-center notice"></p>
        <input type="submit" name="registration-form" value="Submit" style="width: 50%; margin: auto;">
    </form>
   </div> 
</main>
<script>
const password = document.getElementById("password");
const confirmPassword = document.getElementById("password-reenter");

// Check password strength
password.addEventListener("input", function () {
    const value = password.value;

    const isValid =
        value.length >= 8 &&
        /[A-Z]/.test(value) &&
        /[a-z]/.test(value) &&
        /[0-9]/.test(value);

    if (!isValid) {
        password.setCustomValidity("Password must be at least 8 characters, include uppercase, lowercase, and a number.");
    } else {
        password.setCustomValidity("");
    }
});

// Check password match
confirmPassword.addEventListener("input", function () {
    if (confirmPassword.value !== password.value) {
        confirmPassword.setCustomValidity("Passwords do not match.");
    } else {
        confirmPassword.setCustomValidity("");
    }
});
</script>
