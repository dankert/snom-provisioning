<?php


require_once ('lib/Spyc.php');

$DEVICES_YML = 'devices-'.$_SERVER['HTTP_HOST'].'.yml';

if   ( !is_file( $DEVICES_YML ))
    $DEVICES_YML = 'devices.yml';

$devices = Spyc::YAMLLoad($DEVICES_YML);

$contactsCSV = @$devices['phonebook']['file'];

if   ( ! is_file($contactsCSV))
    throw new RuntimeException('file does not exist: '.$contactsCSV);

$handle = fopen($contactsCSV, "r");
$csv = [];
while (($csv[] = fgetcsv($handle)) !== FALSE) {
}

$keys = array_shift($csv);
foreach ($csv as $i=>$row) {
    $csv[$i] = array_combine($keys, $row);
}



if   ( $_GET['action']=='settings')
{
    header('Content-Type: text/xml');
?><settings>
    <?php foreach( $devices['devices'] as $device ) {
        if   (str_replace(':','',@$device['mac'])==$_GET['mac']) {
            $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[SCRIPT_NAME]".'?action=settings&amp;mac={mac}'
            ?>
            <phone-settings e="2">
                <language perm=""><?php echo $devices['system']['language']; ?></language>
                <setting_server perm="RW"><?php echo $actual_link ?></setting_server>
                <ip_adr perm="R"><?php echo $device['ip']; ?></ip_adr>
                <netmask perm="R"><?php echo $devices['system']['netmask']; ?></netmask>
                <dns_domain perm="RW"><?php echo $devices['system']['domain']; ?></dns_domain>
                <dns_server1 perm="RW"><?php echo $devices['system']['gateway']; ?></dns_server1>
                <dhcp perm="">off</dhcp>
                <gateway perm="R"><?php echo $devices['system']['gateway']; ?></gateway>
                <phone_name perm="R"><?php echo $device['host']; ?></phone_name>
                <?php ?></phone-settings>
            <?php
        }
    }?>
    <tbook e="2">
        <?php
        $idx=0;
        foreach($csv as $entry) {
            if   ( !@$entry['Phone 1 - Value']) continue; // Must have a telephone number
          ?><item context="" type="" fav="false" mod="true" index="<?php echo $idx++ ?>">
                <name><?php echo $entry['Name'] ?></name>
                <first_name><?php echo $entry['Given Name'] ?></first_name>
                <last_name><?php echo $entry['Family Name'] ?></last_name>
                <number><?php echo $entry['Phone 1 - Value'] ?></number>
                <!-- "/"sip"/"mobile"/"fixed"/"home"/"business""" -->
                <!--<number_type>sip</number_type>-->
                <email><?php echo $entry['E-mail 1 - Value'] ?></email>
                <note><?php echo $entry['Address 1 - Formatted'] ?></note>
                <?php if (@$entry['Birthday']) { ?>
                <birthday><?php echo date("m/d/Y", strtotime($entry['Birthday'])); ?></birthday>
                <organization><?php echo $entry['Organization 1 - Name']; ?></organization>
                <?php } ?>
            </item><?php
        }
        ?>
    </tbook>
</settings><?php
}


elseif   ( $_GET['action']=='debug')
{
    header('Content-Type: text/plain');
    echo "\n\nDevice configuration:\n\n";
    print_r($devices);
    echo "\n\nPhonebook:\n\n";
    print_r($csv);
}

else {
    header('Content-Type: text/plain');

    echo "Error: No valid action parameter";
}