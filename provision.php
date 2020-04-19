<?php
//error_reporting(E_ALL);

// Initialization
require_once ('lib/Spyc.php');

$DEVICES_YML = 'devices-'.$_SERVER['HTTP_HOST'].'.yml';

if   ( !is_file( $DEVICES_YML ))
    $DEVICES_YML = 'devices.yml';

$devices = Spyc::YAMLLoad($DEVICES_YML);

$contactsCSV = @$devices['phonebook']['file'];

if   ( ! is_file($contactsCSV))
    throw new RuntimeException('file does not exist: '.$contactsCSV);

$handle = fopen($contactsCSV, "r");
$contacts = [];
while (($contacts[] = fgetcsv($handle)) !== FALSE) {
}

$keys = array_shift($contacts);
foreach ($contacts as $i=> $row) {
    $contacts[$i] = @array_combine($keys, $row);
}

$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[SCRIPT_NAME]";



if   ( $_GET['action']=='settings')
{
    // Step 1: Create Phone settings
    header('Content-Type: text/xml');

    $doc = new DOMDocument('1.0','UTF-8');
    $settings = $doc->createElement('settings');
    $doc->appendChild($settings);

    // Phone settings
    foreach( $devices['devices'] as $device ) {
        if   (str_replace(':','',@$device['mac'])==$_GET['mac']) {


            $phoneSettings = [
                'language'      => $devices['system']['language'],
                'web_language'  => $devices['system']['language'],
                'setting_server'=> $actual_link.'?action=settings&amp;mac={mac}',
                'settings_refresh_timer'=> '1800',
                'update_policy' => 'settings_only',
                'ip_adr'        => $device['ip'],
                'netmask'       => $devices['system']['netmask'],
                'dns_domain'    => $devices['system']['domain'],
                'dns_server1'   => $devices['system']['gateway'],
                'dhcp'          => 'off',
                'gateway'       => $devices['system']['gateway'],
                'phone_name'    => $device['host'],
                'http_user'     => $devices['system']['admin']['user'],
                'http_pass'     => $devices['system']['admin']['password'],
                'admin_mode_password'         => $devices['system']['admin']['password'],
                'admin_mode_password_confirm' => $devices['system']['admin']['password']
            ];
            if   ( @$device['ip'] )
            {
                $phoneSettings['ip_adr'] = $device['ip'];
                $phoneSettings['dhcp'  ] = 'off';
            }else {
                $phoneSettings['dhcp'  ] = 'on';
            }
            if   ( @$devices['system']['ntp'] )
                $phoneSettings['ntp_server'] = $devices['system']['ntp'];

            $phonesettings = new DOMElement('phone-settings');
            $settings->appendChild( $phonesettings );
            $phonesettings->setAttribute('e','2');

            foreach( $phoneSettings as $name=>$value )
            {
                $e = new DOMElement($name,$value);
                $phonesettings->appendChild($e);
                $e->setAttribute('perm','RW');
            }

            $userIndex = 1;
            foreach( $device['users'] as $username )
            {
                $user = @$devices['accounts'][$username];
                if   ( !$user )
                    $user = [];
                //$user = array_merge($devices['system'],$user);

                $userSettings = [
                    'active'=>'on',
                    'realname'=>@$user['label']?$user['label']:$username,
                    'pass'=>@$user['password']?$user['password']:@$devices['system']['proxy'],
                    'name'=>@$user['user']?$user['user']:$username,
                    'host'=>@$user['proxy']?$user['proxy']:@$devices['system']['proxy']
                ];

                foreach( $userSettings as $name=>$value )
                {
                    $u = new DOMElement('user_'.$name,$value);
                    $phonesettings->appendChild($u);
                    $u->setAttribute('perm','RW');
                    $u->setAttribute('idx',$userIndex);
                }
                $userIndex++;
            }
        }
    }


    // Step 2: Phone book

    $book = new DOMElement('tbook');
    $settings->appendChild($book);
    $book->setAttribute('e','2');
    $settings->appendChild($book);
    
    $idx=1;
    foreach($contacts as $contact) {
        for( $p = 1; $p <= 6; $p++ ) {

            if   ( !@$contact['Phone '.$p.' - Value']) continue; // Must have a telephone number

            $item = new DOMElement('item');
            $book->appendChild($item);
            $item->setAttribute('context','');
            $item->setAttribute('type','');
            $item->setAttribute('fav','false');
            $item->setAttribute('mod','true');
            $item->setAttribute('index',$idx++);

            $phone = [
                'name'=>$contact['Name'],
                'first_name'=>$contact['Given Name'],
                'last_name'=>$contact['Family Name'],
                'number'=>$contact['Phone '.$p.' - Value'],
                // TODO: number_type "/sip/mobile/fixed/home/business"
                'email'=>$contact['E-mail 1 - Value'],
                'note'=>$contact['Address 1 - Formatted'],
                'organization'=>$contact['Organization 1 - Name']
            ];
            if (@$contact['Birthday'])
                $phone['birthday']=date("m/d/Y", strtotime($contact['Birthday']));

            foreach( $phone as $name=>$value)
                $item->appendChild( new DOMElement($name,$value) );
        }
    }
    echo $doc->saveXML();
}


elseif   ( $_GET['action']=='debug')
{
    header('Content-Type: text/plain');
    echo "\n\nDevice configuration:\n\n";
    print_r($devices);
    echo "\n\nPhonebook:\n\n";
    print_r($contacts);
}

else {
    header('Content-Type: text/plain');

    echo "Error: No valid action parameter";
}