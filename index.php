<?php

$file = 'temp/output.pdf';

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
    require_once('forge_fdf.php');
    
    $fdf_data_names = array();
    $fdf_data_strings = array();
    $fields_hidden = array();
    $fields_readonly = array();
    
    //The stuff that's not in the form
    $fdf_data_strings['fall'] = '15';
    
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
    $fdf_data_strings['projectDescription']      = $_POST['plan'];
    
    //Ethnicity field wasn't created properly, needs to be done this way :(
    if(isset($_POST['ethnicity-africanamerican']) && !empty($_POST['ethnicity-africanamerican']))
        $fdf_data_strings['AfricanAmerican'] = 'Yes';
    
    if(isset($_POST['ethnicity-hispanic']) && !empty($_POST['ethnicity-hispanic']))
        $fdf_data_strings['Hispanic'] = 'Yes';
    
    if(isset($_POST['ethnicity-nativeamerican']) && !empty($_POST['ethnicity-nativeamerican']))
        $fdf_data_strings['nativeAmerican'] = 'Yes';
    
    if(isset($_POST['ethnicity-other']) && !empty($_POST['ethnicity-other']))
        $fdf_data_strings['Other'] = 'Yes';
    
    $fdf = forge_fdf("", $fdf_data_strings, $fdf_data_names, $fields_hidden, $fields_readonly);
    
    file_put_contents('temp/urp.fdf', $fdf);
    exec('pdftk URP_Application.pdf fill_form temp/urp.fdf output temp/output.pdf flatten');
    
    if (file_exists($file))
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.basename($file));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        exit;
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Automatic URP Generator</title>
</head>
<body>
    <h1>Automatic URP Generator</h1>
    <h2>So you can copy/paste and stuff</h2>
    <form action="" method="POST" id="urp">
        Full Name:
            <input type="text" name="name">
        <br>
        Sex:
            <input type="radio" name="sex" value="Male">Male
            <input type="radio" name="sex" value="Female">Female
        <br>
        Date of Birth:
            <input type="text" name="dob">
        <br>
        Campus or Local Address:
            <input type="text" name="address1"><br>
            <input type="text" name="address2"><br>
            <input type="text" name="address3"><br>
        <br>
        Campus/Local Phone:
            <input type="text" name="phone">
        <br>
        Email:
            <input type="text" name="email">
        <br>
        RIN:
            <input type="text" name="rin">
        <br>
        <hr>
        <br>
        Degree Program:
            <input type="text" name="degree">
        <br>
        Year:
            <input type="radio" name="year" value="first year">First Year
            <input type="radio" name="year" value="sophomore">Sophomore
            <input type="radio" name="year" value="junior">Junior
            <input type="radio" name="year" value="senior">Senior
        <br>
        U.S. Citizen:
            <input type="radio" name="citizen" value="Yes">Yes
            <input type="radio" name="citizen" value="No">No
        <br>
        If no, country of citizenship:
            <input type="text" name="altcitizen">
        <br>
        Do you have an interest in teaching in the future:
            <input type="radio" name="teaching" value="Yes">Yes
            <input type="radio" name="teaching" value="No">No
        <br>
        Ethnicity:
            <input type="checkbox" name="ethnicity-africanamerican" value="Yes">Afr. Am.
            <input type="checkbox" name="ethnicity-hispanic" value="Yes">Hisp.
            <input type="checkbox" name="ethnicity-nativeamerican" value="Yes">Native Am.
            <input type="checkbox" name="ethnicity-other" value="Yes">Other
        <br>
        Compensation:
            <input type="radio" name="compensation" value="Credit">Credit
            <input type="radio" name="compensation" value="funding">Funding
            <input type="radio" name="compensation" value="experience">For the Experience
        <br>
        Research Plan:
            <textarea name="plan" form="urp"></textarea>
        <br>
        <input type="submit" value="Submit">
    </form>
</body>
</html>
