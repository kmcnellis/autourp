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
$infile = 'URP_Application.pdf';

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
        
        $formfile = sys_get_temp_dir().'/form-'.$clean_name.'-'.$clean_title.'.fdf';
        $outfile = sys_get_temp_dir().'/urp-rcos-'.$clean_name.'-'.$clean_title.'.pdf';
        
        $fdf_data_names = array();
        $fdf_data_strings = array();
        $fields_hidden = array();
        $fields_readonly = array();
        
        //The stuff that's not in the form
        $fdf_data_strings['fall'] = '15';
        $fdf_data_strings['facultySupervisorName'] = 'David Goldschmidt';
        $fdf_data_strings['facultySupervisorDepartment'] = 'CSCI';
        $fdf_data_strings['facultySupervisorCampusPhone'] = 'x2819';
        $fdf_data_strings['facultySupervisorEmailAddress'] = 'gol'.'dsch'.'midt'.'@'.'gmail'.'.'.'com'; //maybe prevent some spam?
        
        $fdf_data_strings['studentName']             = $_POST['name'];
        $fdf_data_strings['maleFemale']              = $_POST['sex'];
        $fdf_data_strings['dateOfBirth']             = $_POST['dob'];
        $fdf_data_strings['Address 1']               = $_POST['address1'];
        $fdf_data_strings['Address 2']               = $_POST['address2'];
        $fdf_data_strings['cityStateZip']            = $_POST['address3'];
        $fdf_data_strings['phoneNumber']             = $_POST['phone'];
        $fdf_data_strings['emailAddress']            = $_POST['email'];
        $fdf_data_strings['RIN']                     = $_POST['rin'];
        $fdf_data_strings['degreeProgram']           = $_POST['degree'];
        $fdf_data_strings['rpiYear']                 = $_POST['year'];
        $fdf_data_strings['usCitizenship']           = $_POST['citizen'];
        $fdf_data_strings['countryOfCitizenship']    = $_POST['altcitizen'];
        $fdf_data_strings['interestInTeaching']      = $_POST['teaching'];
        $fdf_data_strings['creditFundingExperience'] = $_POST['compensation'];
        $fdf_data_strings['projectTitle']            = 'RCOS - '.$_POST['title'];
        $fdf_data_strings['projectDescription']      = $_POST['plan'];
        
        //Ethnicity field wasn't created properly, needs to be done this way :(
        if(validinput($_POST['ethnicity-africanamerican']))
            $fdf_data_strings['AfricanAmerican'] = 'Yes';
        
        if(validinput($_POST['ethnicity-hispanic']))
            $fdf_data_strings['Hispanic'] = 'Yes';
        
        if(validinput($_POST['ethnicity-nativeamerican']))
            $fdf_data_strings['nativeAmerican'] = 'Yes';
        
        if(validinput($_POST['ethnicity-other']))
            $fdf_data_strings['Other'] = 'Yes';
        
        $fdf = forge_fdf("", $fdf_data_strings, $fdf_data_names, $fields_hidden, $fields_readonly);
        
        file_put_contents($formfile, $fdf);
        exec('pdftk '.escapeshellcmd($infile).' fill_form '.escapeshellcmd($formfile).' output '.escapeshellcmd($outfile).' flatten');
        
        if (file_exists($outfile))
        {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.basename($outfile));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($outfile));
            ob_clean();
            flush();
            readfile($outfile);
            exit;
        }
        else
        {
            $errors = true;
            $error_message = "Internal error generating URP. Reach out to @robert-rouhani on Slack.";
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
    <h1>Automatic URP Generator</h1>
    <h2>So you can copy/paste and stuff</h2>
    <p class="info message">All fields are required.</p>
    <?php if ($error_message != "") echo '<p class="error message">'.$error_message.'</p>'; ?>
    <form action="/" method="POST" id="urp">
    
        <div class="section">
            <p class="label <?php if ($errors && !validinput($_POST['name'])) echo 'error'; ?>">Full Name</p>
            <input type="text" name="name" placeholder="Jane Doe" value="<?php echo $_POST['name']; ?>">
            
            <p class="label <?php if ($errors && !validinput($_POST['sex'])) echo 'error'; ?>">Sex</p>
            <input type="radio" name="sex" value="Male" <?php if ($_POST['sex'] == "Male") echo 'checked';?>>Male
            <input type="radio" name="sex" value="Female" <?php if ($_POST['sex'] == "Female") echo 'checked';?>>Female
            
            <p class="label <?php if ($errors && !validinput($_POST["dob"])) echo 'error'; ?>">Date of Birth</p>
            <input type="text" name="dob" placeholder="MM/DD/YYYY" value="<?php echo $_POST["dob"]; ?>">
            
            <p class="label <?php if ($errors && (!validinput($_POST["address1"]) || (!validinput($_POST["address2"]) && !validinput($_POST["address3"])))) echo 'error'; ?>">Campus or Local Address</p>
            <input type="text" name="address1" placeholder="110 8th St." value="<?php echo $_POST["address1"]; ?>"><br>
            <input type="text" name="address2" placeholder="Apt 1337" value="<?php echo $_POST["address2"]; ?>"><br>
            <input type="text" name="address3" placeholder="Troy, NY 12180" value="<?php echo $_POST["address3"]; ?>">
            
            <p class="label <?php if ($errors && !validinput($_POST["phone"])) echo 'error'; ?>">Campus/Local Phone</p>
            <input type="text" name="phone" placeholder="(518) 555-5555" value="<?php echo $_POST["phone"]; ?>">
            
            <p class="label <?php if ($errors && !validinput($_POST["email"])) echo 'error'; ?>">Email</p>
            <input type="text" name="email" placeholder="me@rpi.edu" value="<?php echo $_POST["email"]; ?>">
            
            <p class="label <?php if ($errors && !validinput($_POST["rin"])) echo 'error'; ?>">RIN</p>
            <input type="text" name="rin" placeholder="660000000" value="<?php echo $_POST["rin"]; ?>">
        </div>
        
        <div class="section">
            <p class="label <?php if ($errors && !validinput($_POST["degree"])) echo 'error'; ?>">Degree Program</p>
            <input type="text" name="degree" placeholder="CSCI" value="<?php echo $_POST["degree"]; ?>">
            
            <p class="label <?php if ($errors && !validinput($_POST["year"])) echo 'error'; ?>">Year</p>
            <input type="radio" name="year" value="first year" <?php if ($_POST['year'] == "first year") echo 'checked';?>>First Year
            <input type="radio" name="year" value="sophomore" <?php if ($_POST['year'] == "sophomore") echo 'checked';?>>Sophomore
            <input type="radio" name="year" value="junior" <?php if ($_POST['year'] == "junior") echo 'checked';?>>Junior
            <input type="radio" name="year" value="senior" <?php if ($_POST['year'] == "senior") echo 'checked';?>>Senior
            
            <p class="label <?php if ($errors && !validinput($_POST["citizen"])) echo 'error'; ?>">U.S. Citizen</p>
            <input type="radio" name="citizen" value="Yes" <?php if ($_POST['citizen'] == "Yes") echo 'checked';?>>Yes
            <input type="radio" name="citizen" value="No" <?php if ($_POST['citizen'] == "No") echo 'checked';?>>No
            
            <p class="indented label <?php if ($errors && $_POST['citizen'] == "No" && !validinput($_POST["altcitizen"])) echo 'error'; ?>">If no, country of citizenship</p>
            <input class="indented" type="text" name="altcitizen" value="<?php echo $_POST["altcitizen"]; ?>">
            
            <p class="label <?php if ($errors && !validinput($_POST["teaching"])) echo 'error'; ?>">Do you have an interest in teaching in the future?</p>
            <input type="radio" name="teaching" value="Yes" <?php if ($_POST['teaching'] == "Yes") echo 'checked';?>>Yes
            <input type="radio" name="teaching" value="No" <?php if ($_POST['teaching'] == "No") echo 'checked';?>>No
            
            <p class="label <?php if ($errors && !validinput($_POST["ethnicity-africanamerican"]) && !validinput($_POST["ethnicity-hispanic"]) && !validinput($_POST["ethnicity-nativeamerican"]) && !validinput($_POST["ethnicity-other"])) echo 'error'; ?>">Ethnicity</p>
            <input type="checkbox" name="ethnicity-africanamerican" value="Yes" <?php if ($_POST['ethnicity-africanamerican'] == "Yes") echo 'checked';?>>Afr. Am.
            <input type="checkbox" name="ethnicity-hispanic" value="Yes" <?php if ($_POST['ethnicity-hispanic'] == "Yes") echo 'checked';?>>Hisp.
            <input type="checkbox" name="ethnicity-nativeamerican" value="Yes" <?php if ($_POST['ethnicity-nativeamerican'] == "Yes") echo 'checked';?>>Native Am.
            <input type="checkbox" name="ethnicity-other" value="Yes" <?php if ($_POST['ethnicity-other'] == "Yes") echo 'checked';?>>Other
            
            <p class="label <?php if ($errors && !validinput($_POST["compensation"])) echo 'error'; ?>">Compensation</p>
            <input type="radio" name="compensation" value="Credit" <?php if ($_POST['compensation'] == "Credit") echo 'checked';?>>Credit
            <input type="radio" name="compensation" value="funding" <?php if ($_POST['compensation'] == "funding") echo 'checked';?>>Funding
            <input type="radio" name="compensation" value="experience" <?php if ($_POST['compensation'] == "experience") echo 'checked';?>>For the Experience
            
            <p class="label <?php if ($errors && !validinput($_POST["title"])) echo 'error'; ?>">Project Title</p>
            <input type="text" name="title" value="<?php echo $_POST["title"]; ?>">
        </div>
        
        <div class="section-wide">
            <p class="label <?php if ($errors && !validinput($_POST["plan"])) echo 'error'; ?>">Project Plan</p>
            <textarea name="plan" form="urp"><?php echo $_POST["plan"]; ?></textarea>
        </div>
        
        <div class="section-wide">
            <input type="submit" value="Submit">
        </div>
    </form>
</body>
</html>
