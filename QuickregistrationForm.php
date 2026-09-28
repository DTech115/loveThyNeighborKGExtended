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
            
        
        </fieldset>

        <fieldset class="section-box mb-4">
            <h3>Contact Information</h3>
            <p class="mb-2">The following information will help us determine the best way to contact you regarding event coordination.</p>
	    <div class="blue-div"></div>
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
            <p>I confirm that I have read the <a href="https://www.kgfood.org/privacy">Privacy Policy</a> and consent to the Love Thy Neighbor Community Food Pantry in King George, Virginia, collecting and storing my information for the purposes outlined therein.</p>
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
                <!--<div class="radio-element">
                    <input type="radio" id="disagree" name="photo_release" value="no">
                    <label for="disagree">I do not agree.</label>
                </div>-->
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
