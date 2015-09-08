<?php

function validinput($var)
{
    return isset($var) && !empty($var);
}

//http://stackoverflow.com/a/2021729/1122135
function sanitize_for_file_name($var)
{
    // Remove anything which isn't a word, whitespace, number
    // or any of the following caracters -_~,;:[]().
    $var = preg_replace("([^\w\s\d\-_~,;:\[\]\(\).])", '', $var);
    $var = preg_replace("([\.]{2,})", '', $var);
    return str_replace(' ', '', $var);
}

$errors = false;
$error_message = "";
$infileurp = 'URP_Application.pdf';
$infile4ur = '4ur.pdf';

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
    if (validinput($_POST['name']) &&
        validinput($_POST['sex']) &&
        validinput($_POST['dob']) &&
        validinput($_POST['address1']) &&
        (
            validinput($_POST['address2']) ||
            validinput($_POST['address3'])
        ) &&
        validinput($_POST['phone']) &&
        validinput($_POST['email']) &&
        validinput($_POST['rin']) &&
        validinput($_POST['degree']) &&
        validinput($_POST['year']) &&
        validinput($_POST['citizen']) &&
        (
            $_POST['citizen'] == "Yes" ||
            validinput($_POST['altcitizen'])
        ) &&
        validinput($_POST['teaching']) &&
        validinput($_POST['compensation']) &&
        (
            $_POST['compensation'] != "Credit" ||
            validinput($_POST['creditamount'])
        ) &&
        validinput($_POST['title']) &&
        validinput($_POST['plan']) &&
        (
            validinput($_POST['ethnicity-africanamerican']) ||
            validinput($_POST['ethnicity-hispanic']) ||
            validinput($_POST['ethnicity-nativeamerican']) ||
            validinput($_POST['ethnicity-other'])
        ))
    {
        require_once('forge_fdf.php');
        
        $clean_name = sanitize_for_file_name($_POST['name']);
        $clean_title = sanitize_for_file_name($_POST['title']);
        
        //Fill in the URP form
        $formfileurp = sys_get_temp_dir().'/form-urp-'.$clean_name.'-'.$clean_title.uniqid().'.fdf';
        $outfileurp = sys_get_temp_dir().'/urp-rcos-'.$clean_name.'-'.$clean_title.uniqid().'.pdf';
        
        $urp_data_names = array();
        $urp_data_strings = array();
        $urp_hidden = array();
        $urp_readonly = array();
        
        //The stuff that's not in the form
        $urp_data_strings['fall'] = '15';
        $urp_data_strings['facultySupervisorName'] = 'David Goldschmidt';
        $urp_data_strings['facultySupervisorDepartment'] = 'CSCI';
        $urp_data_strings['facultySupervisorCampusPhone'] = 'x2819';
        $urp_data_strings['facultySupervisorEmailAddress'] = 'gol'.'dsch'.'midt'.'@'.'gmail'.'.'.'com'; //maybe prevent some spam?
        
        $urp_data_strings['studentName']             = $_POST['name'];
        $urp_data_strings['maleFemale']              = $_POST['sex'];
        $urp_data_strings['dateOfBirth']             = $_POST['dob'];
        $urp_data_strings['Address 1']               = $_POST['address1'];
        $urp_data_strings['Address 2']               = $_POST['address2'];
        $urp_data_strings['cityStateZip']            = $_POST['address3'];
        $urp_data_strings['phoneNumber']             = $_POST['phone'];
        $urp_data_strings['emailAddress']            = $_POST['email'];
        $urp_data_strings['RIN']                     = $_POST['rin'];
        $urp_data_strings['degreeProgram']           = $_POST['degree'];
        $urp_data_strings['rpiYear']                 = $_POST['year'];
        $urp_data_strings['usCitizenship']           = $_POST['citizen'];
        $urp_data_strings['interestInTeaching']      = $_POST['teaching'];
        $urp_data_strings['creditFundingExperience'] = $_POST['compensation'];
        $urp_data_strings['projectTitle']            = 'RCOS - '.$_POST['title'];
        $urp_data_strings['projectDescription']      = $_POST['plan'];
        
        //only fill altcitizen if citizen is not Yes
        if ($_POST['citizen'] != "Yes")
            $urp_data_strings['countryOfCitizenship'] = $_POST['altcitizen'];
        
        //Ethnicity field wasn't created properly, needs to be done this way :(
        if(validinput($_POST['ethnicity-africanamerican']))
            $urp_data_strings['AfricanAmerican'] = 'Yes';
        
        if(validinput($_POST['ethnicity-hispanic']))
            $urp_data_strings['Hispanic'] = 'Yes';
        
        if(validinput($_POST['ethnicity-nativeamerican']))
            $urp_data_strings['nativeAmerican'] = 'Yes';
        
        if(validinput($_POST['ethnicity-other']))
            $urp_data_strings['Other'] = 'Yes';
        
        $fdfurp = forge_fdf("", $urp_data_strings, $urp_data_names, $urp_hidden, $urp_readonly);
        
        file_put_contents($formfileurp, $fdfurp);
        exec('pdftk '.escapeshellcmd($infileurp).' fill_form '.escapeshellcmd($formfileurp).' output '.escapeshellcmd($outfileurp).' flatten');
        
        if (file_exists($outfileurp))
        {
            if ($_POST['compensation'] != 'Credit')
            {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename='.basename($outfileurp));
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . filesize($outfileurp));
                ob_clean();
                flush();
                readfile($outfileurp);
                exit;
            }
        }
        else
        {
            $errors = true;
            $error_message = "Internal error generating URP. Reach out to @robert-rouhani on Slack.";
        }
        
        //Fill in the 4UR form
        if ($_POST['compensation'] == 'Credit' && !$errors)
        {
            $formfile4ur = sys_get_temp_dir().'/form-4ur-'.$clean_name.'-'.$clean_title.uniqid().'.fdf';
            $outfile4ur = sys_get_temp_dir().'/4ur-rcos-'.$clean_name.'-'.$clean_title.uniqid().'.pdf';
            
            $_4ur_data_names = array();
            $_4ur_data_strings = array();
            $_4ur_hidden = array();
            $_4ur_readonly = array();
            
            date_default_timezone_set('America/New_York');
            $_4ur_data_strings['Date']                    = date('m/d/Y');
            $_4ur_data_strings['Fall']                    = '15';
            $_4ur_data_strings['Subject Code']            = 'CSCI';
            $_4ur_data_strings['Print Instructors Name']  = 'Goldschmidt, David';
            $_4ur_data_strings['specific role of student in the project 1'] = 'Please see attached';
            $_4ur_data_strings['indicate expected weekly time commitments 1'] = 'Please see attached';
            $_4ur_data_strings['determined 1'] = 'Please see attached';
            
            $_4ur_data_strings['Name']                    = $_POST['name']; //TODO follow the Last, First format in the actual form
            $_4ur_data_strings['Rensselaer ID']           = $_POST['rin'];
            $_4ur_data_strings['Email']                   = $_POST['email'];
            $_4ur_data_strings['Day phone']               = $_POST['phone'];
            $_4ur_data_strings['Credit Hours']            = $_POST['creditamount'];
            $_4ur_data_strings['Transcript Course Title'] = 'RCOS - '.$_POST['title'];
            
            $fdf4ur = forge_fdf("", $_4ur_data_strings, $_4ur_data_names, $_4ur_hidden, $_4ur_readonly);
            
            file_put_contents($formfile4ur, $fdf4ur);
            exec('pdftk '.escapeshellcmd($infile4ur).' fill_form '.escapeshellcmd($formfile4ur).' output '.escapeshellcmd($outfile4ur).' flatten');
            
            $outfilemerge = sys_get_temp_dir().'/urp-4ur-rcos-'.$clean_name.'-'.$clean_title.uniqid().'.pdf';
            exec('pdftk '.escapeshellcmd($outfileurp).' '.escapeshellcmd($outfile4ur).' cat output '.escapeshellcmd($outfilemerge));
            
            if (file_exists($outfilemerge))
            {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename='.basename($outfilemerge));
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . filesize($outfilemerge));
                ob_clean();
                flush();
                readfile($outfilemerge);
                exit;
            }
            else
            {
                $errors = true;
                $error_message = "Internal error generating 4UR. Reach out to @robert-rouhani on Slack.";
            }
        }
    }
    else
    {
        $errors = true;
        $error_message = "Some fields are missing or invalid. Please fix the ones highlighted in red.";
    }
}


?>

<!DOCTYPE html>
<html>
<head>
    <title>Automatic URP Generator</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
    <a class="return-link" href="http://rcos.io">&lt;-- Return to Observatory</a>
    <span> | </span>
    <a class="return-link" href="/URP_Application.pdf">Original URP Form</a>
    <span> | </span>
    <a class="return-link" href="/4ur.pdf">Original 4UR Form</a>
    <h1>Automatic URP Generator</h1>
    <h2>So you can copy/paste and stuff</h2>
    <p class="info message"><b>All fields are required.</b></p>
    <?php if ($error_message != "") echo '<p class="error message"><b>'.$error_message.'</b></p>'; ?>
    
    <div class="info message">
    <b>Instructions:</b> Fill out this form and press the Submit button on the bottom.
    <br>
    <br>
    If there are no errors, a file will start downloading. Print the
    form out and sign and date the first page (on the bottom left right under
    "Signatures").
    <br>
    <br>
    Bring the stack of forms with you to the RCOS meeting on
    Friday 9/11 or put it in Goldschmidt's mailbox in Lally (second floor).
    </div>
    
    <form action="/" method="POST" id="urp">
    
        <div class="section">
            <p class="label <?php if ($errors && !validinput($_POST['name'])) echo 'error'; ?>">Full Name</p>
            <input type="text" name="name" placeholder="Jane Doe" value="<?php echo $_POST['name']; ?>">
            
            <p class="label <?php if ($errors && !validinput($_POST['sex'])) echo 'error'; ?>">Sex</p>
            <input type="radio" name="sex" value="Male" <?php if ($_POST['sex'] == "Male") echo 'checked';?>>Male
            <input type="radio" name="sex" value="Female" <?php if ($_POST['sex'] == "Female") echo 'checked';?>>Female
            
            <p class="label <?php if ($errors && !validinput($_POST['dob'])) echo 'error'; ?>">Date of Birth</p>
            <input type="text" name="dob" placeholder="MM/DD/YYYY" value="<?php echo $_POST['dob']; ?>">
            
            <p class="label <?php if ($errors && (!validinput($_POST['address1']) || (!validinput($_POST['address2']) && !validinput($_POST['address3'])))) echo 'error'; ?>">Campus or Local Address</p>
            <input type="text" name="address1" placeholder="110 8th St." value="<?php echo $_POST['address1']; ?>"><br>
            <input type="text" name="address2" placeholder="Apt 1337" value="<?php echo $_POST['address2']; ?>"><br>
            <input type="text" name="address3" placeholder="City, ZZ 11111" value="<?php echo $_POST['address3']; ?>">
            
            <p class="label <?php if ($errors && !validinput($_POST['phone'])) echo 'error'; ?>">Campus/Local Phone</p>
            <input type="text" name="phone" placeholder="(518) 555-5555" value="<?php echo $_POST['phone']; ?>">
            
            <p class="label <?php if ($errors && !validinput($_POST['email'])) echo 'error'; ?>">Email</p>
            <input type="text" name="email" placeholder="me@rpi.edu" value="<?php echo $_POST['email']; ?>">
            
            <p class="label <?php if ($errors && !validinput($_POST['rin'])) echo 'error'; ?>">RIN</p>
            <input type="text" name="rin" placeholder="660000000" value="<?php echo $_POST['rin']; ?>">
        </div>
        
        <div class="section">
            <p class="label <?php if ($errors && !validinput($_POST['degree'])) echo 'error'; ?>">Degree Program</p>
            <input type="text" name="degree" placeholder="CSCI" value="<?php echo $_POST['degree']; ?>">
            
            <p class="label <?php if ($errors && !validinput($_POST['year'])) echo 'error'; ?>">Year</p>
            <input type="radio" name="year" value="first year" <?php if ($_POST['year'] == "first year") echo 'checked';?>>First Year
            <input type="radio" name="year" value="sophomore" <?php if ($_POST['year'] == "sophomore") echo 'checked';?>>Sophomore
            <input type="radio" name="year" value="junior" <?php if ($_POST['year'] == "junior") echo 'checked';?>>Junior
            <input type="radio" name="year" value="senior" <?php if ($_POST['year'] == "senior") echo 'checked';?>>Senior
            
            <p class="label <?php if ($errors && !validinput($_POST['citizen'])) echo 'error'; ?>">U.S. Citizen</p>
            <input type="radio" name="citizen" value="Yes" <?php if ($_POST['citizen'] == "Yes") echo 'checked';?>>Yes
            <input type="radio" name="citizen" value="No" <?php if ($_POST['citizen'] == "No") echo 'checked';?>>No
            
            <p class="indented label <?php if ($errors && $_POST['citizen'] == "No" && !validinput($_POST['altcitizen'])) echo 'error'; ?>">If no, country of citizenship</p>
            <input class="indented" type="text" name="altcitizen" value="<?php echo $_POST['altcitizen']; ?>">
            
            <p class="label <?php if ($errors && !validinput($_POST['teaching'])) echo 'error'; ?>">Do you have an interest in teaching in the future?</p>
            <input type="radio" name="teaching" value="Yes" <?php if ($_POST['teaching'] == "Yes") echo 'checked';?>>Yes
            <input type="radio" name="teaching" value="No" <?php if ($_POST['teaching'] == "No") echo 'checked';?>>No
            
            <p class="label <?php if ($errors && !validinput($_POST['ethnicity-africanamerican']) && !validinput($_POST['ethnicity-hispanic']) && !validinput($_POST['ethnicity-nativeamerican']) && !validinput($_POST['ethnicity-other'])) echo 'error'; ?>">Ethnicity</p>
            <input type="checkbox" name="ethnicity-africanamerican" value="Yes" <?php if ($_POST['ethnicity-africanamerican'] == "Yes") echo 'checked';?>>Afr. Am.
            <input type="checkbox" name="ethnicity-hispanic" value="Yes" <?php if ($_POST['ethnicity-hispanic'] == "Yes") echo 'checked';?>>Hisp.
            <input type="checkbox" name="ethnicity-nativeamerican" value="Yes" <?php if ($_POST['ethnicity-nativeamerican'] == "Yes") echo 'checked';?>>Native Am.
            <input type="checkbox" name="ethnicity-other" value="Yes" <?php if ($_POST['ethnicity-other'] == "Yes") echo 'checked';?>>Other
            
            <p class="label <?php if ($errors && !validinput($_POST['compensation'])) echo 'error'; ?>">Compensation</p>
            <input type="radio" name="compensation" value="Credit" <?php if ($_POST['compensation'] == "Credit") echo 'checked';?>>Credit
            <input type="radio" name="compensation" value="funding" <?php if ($_POST['compensation'] == "funding") echo 'checked';?>>Funding
            <input type="radio" name="compensation" value="experience" <?php if ($_POST['compensation'] == "experience") echo 'checked';?>>For the Experience
            
            <p class="indented label <?php if ($errors && $_POST['compensation'] == "Credit" && !validinput($_POST['creditamount'])) echo 'error'; ?>">Credit Amount</p>
            <input class="indented" type="radio" name="creditamount" value="3" <?php if ($_POST['creditamount'] == "3") echo 'checked';?>>3 credits
            <input class="indented" type="radio" name="creditamount" value="4" <?php if ($_POST['creditamount'] == "4") echo 'checked';?>>4 credits
            
            <p class="label <?php if ($errors && !validinput($_POST['title'])) echo 'error'; ?>">Project Title</p>
            <input type="text" name="title" value="<?php echo $_POST['title']; ?>">
        </div>
        
        <div class="section-wide">
            <p class="label <?php if ($errors && !validinput($_POST['plan'])) echo 'error'; ?>">Project Plan</p>
            <textarea name="plan" form="urp"><?php echo $_POST['plan']; ?></textarea>
        </div>
        
        <div class="section-wide">
            <input type="submit" value="Submit">
        </div>
    </form>
</body>
</html>
