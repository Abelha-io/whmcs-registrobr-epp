<?php

# Copyright (c) 2012-2013, AllWorldIT and (c) 2013, NIC.br (R)
# 
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

# 
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

# This module is a fork from whmcs-coza-epp (http://devlabs.linuxassist.net/projects/whmcs-coza-epp)
# whmcs-coza-epp developed by Nigel Kukard (nkukard@lbsd.net)



# Official Website for whmcs-registrobr-epp
# https://github.com/registrobr/whmcs-registrobr-epp


# More information on NIC.br(R) domain registration services, Registro.br(TM), can be found at http://registro.br
# Information for registrars available at http://registro.br/provedor/epp

# NIC.br(R) is a not-for-profit organization dedicated to domain registrations and fostering of the Internet in Brazil. No WHMCS services of any kind are available from NIC.br(R).


# WHMCS hosting, theming, module development, payment gateway 
# integration, customizations and consulting all available from 
# http://allworldit.com


# Configuration array

$include_path = ROOTDIR . '/modules/registrars/registrobr';
set_include_path($include_path . PATH_SEPARATOR . get_include_path());

#Dependencias => pear.php

/*
echo "inicio";

#testmode
define("ROOTDIR", '/opt/sites/workspace/whmcs-registrobr-epp-org/');

 
$params = array();

registrobr_GetNameservers($params);


echo "fim";
*/

function registrobr_getConfigArray() {

    # Create version table if it doesn't exist
    $query = "CREATE TABLE IF NOT EXISTS `mod_registrobr_version` (
    `version` int(10) unsigned NOT NULL,
    PRIMARY KEY (`version`)
    ) ";
    mysql_query($query);
   
    $current_version = 1.01 ;
    $queryresult = mysql_query("SELECT version FROM mod_registrobr_version");
    $data = mysql_fetch_array($queryresult);
    
    $version=$data['version'];
    
    if ($version!=$current_version) {
        #include code to alter table mod_registrobr
        
        #only update version if alter table above succeeds
        mysql_query("UPDATE mod_registrobr_version SET version='".$current_version."'");
        if (mysql_affected_rows()==0) {
            mysql_query("insert into mod_registrobr_version (version) values ('".$current_version."')");
            mysql_query("ALTER TABLE mod_registrobr CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
        }
    }
  
    
    # Create auxiliary table if it doesn't exist
    $query = "CREATE TABLE IF NOT EXISTS `mod_registrobr` (
        `clID` varchar(16) COLLATE latin1_general_ci NOT NULL,
        `domainid` int(10) unsigned NOT NULL,
        `domain` varchar(200) COLLATE latin1_general_ci NOT NULL,
        `ticket` int(10) unsigned NOT NULL,
        PRIMARY KEY (`domainid`),
        UNIQUE KEY `ticket` (`ticket`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";
    mysql_query($query);
    
	$configarray = array(
		"Username" => array( "Type" => "text", "Size" => "16", "Description" => "Provider ID(numerical)" ),
		"Password" => array( "Type" => "password", "Size" => "20", "Description" => "EPP Password" ),
		"TestMode" => array( "Type" => "yesno" , "Description" => "If active connects to beta.registro.br instead of production server"),
		"Certificate" => array( "Type" => "text", "Description" => "Path of certificate .pem" ),
		"Passphrase" => array( "Type" => "password", "Size" => "20", "Description" => "Passphrase to the certificate file" ),
		"CPF" => array( "Type" => "dropdown", "Options" => "1,2,3,4,5,6,7,8,9", "Description" => "Custom field index for individuals Tax Payer IDs", "Default" => "1"),
        "CNPJ" => array( "Type" => "dropdown", "Options" => "1,2,3,4,5,6,7,8,9", "Description" => "Custom field index for corporations Tax Payer IDs (can be same as above)", "Default" => "1"),
        "TechC" => array( "FriendlyName" => "Tech Contact", "Type" => "text", "Size" => "20", "Description" => "Tech Contact used in new registrations; blank will make registrant the Tech contact" ),
        "TechDept" => array( "FriendlyName" => "Tech Department ID", "Type" => "dropdown", "Options" => "1,2,3,4,5,6,7,8,9", "Description" => "Index for Tech Department ID within ticketing system", "Default" => "1"),
        "FinanceDept" => array( "FriendlyName" => "Finance Department ID", "Type" => "dropdown", "Options" => "1,2,3,4,5,6,7,8,9", "Description" => "Index for Finance Department ID within ticketing system (can be same as above)", "Default" => "1"),
        "Sender" => array( "FriendlyName" => "Sender Username", "Type" => "text", "Size" => "16", "Description" => "Sender of tickets (usually root)", "Default" => "root"),                  
        "Language" => array ( "Type" => "radio", "Options" => "English,Portuguese", "Description" => "Escolha Portuguese para mensagens em Portugu&ecircs", "Default" => "English"),
        "FriendlyName" => array("Type" => "System", "Value"=>"Registro.br"),
        "Description" => array("Type" => "System", "Value"=>"http://registro.br/provedor/epp/"),
        

	);
    return $configarray;

}




# Function to return current nameservers

function registrobr_GetNameservers($params) {

	
	require_once('RegistroEPP/RegistroEPPFactory.class.php');
	require_once('ParserResponse/ParserResponse.class.php');

	$domain = $params["sld"].".".$params["tld"];

	# Grab module parameters
	$moduleparams = getregistrarconfigoptions('registrobr');
	
	$objRegistroEPP = RegistroEPPFactory::build('RegistroEPPDomain');
	$objRegistroEPP->set('domain',$domain);
	
	try {
		$objRegistroEPP->login($moduleparams);
	}
	catch (Exception $e){
		$values["error"] = $e->getMessage();
		return $values;
	}

	do {
		try {
			//Request domain info
			$objRegistroEPP->getInfo();
		}
		catch (Exception $e){
			$values["error"] = $e->getMessage();
			return $values;
		}
		
		$coderes = $objRegistroEPP->get('coderes');
  
        # Check results	

        if($coderes != '1000') {
	        $table = "mod_registrobr";
	        $fields = "clID,domainid,domain,ticket";
	        # incluir domainid ?
	        $where = array("clID"=>$moduleparams['Username'],"domain"=>$domain);
	        $result = select_query($table,$fields,$where);
	        $data = mysql_fetch_array($result);
	        
	        $ticket = $data['ticket'];
	        $objRegistroEPP->set('ticket',$ticket);
        }
    } while ($ticket);
    
    
    return $objRegistroEPP->get('nameservers');

}

# Function to save set of nameservers

function registrobr_SaveNameservers($params) {
    
	
	require_once('RegistroEPP/RegistroEPPFactory.class.php');
	
	$domain = $params["sld"].".".$params["tld"];
	
	# Grab module parameters
	$moduleparams = getregistrarconfigoptions('registrobr');
	
	$objRegistroEPP = RegistroEPPFactory::build('RegistroEPPDomain');
	$objRegistroEPP->set('domain',$domain);
	
	try {
		$objRegistroEPP->login($moduleparams);
	}
	catch (Exception $e){
		$values["error"] = $e->getMessage();
		return $values;
	}
	
	
	$OldNameservers = registrobr_GetNameservers($params);
	
	$NewNameservers["ns1"] = $params["ns1"];
	$NewNameservers["ns2"] = $params["ns2"];
	$NewNameservers["ns3"] = $params["ns3"];
	$NewNameservers["ns4"] = $params["ns4"];
	$NewNameservers["ns5"] = $params["ns5"];
	
	
	$objRegistroEPP->updateNameServers($OldNameservers,$NewNameservers);
	
    return $values;
}


function registrobr_RegisterDomain($params){
	
	require_once('RegistroEPP/RegistroEPPFactory.class.php');
	require_once ('isCnpjValid.php');
	require_once ('isCpfValid.php');
	
	$domain = $params["sld"].".".$params["tld"];
	
	# Grab module parameters
	$moduleparams = getregistrarconfigoptions('registrobr');

	$RegistrantTaxID = $params['customfields'.$moduleparams['CPF']];

    if (!isCpfValid($RegistrantTaxID)) {
    	$RegistrantTaxID = $params['customfields'.$moduleparams['CNPJ']] ;
        
        if (!isCnpjValid($RegistrantTaxID)) {
        	$values["error"] =_registrobr_lang("cpfcnpjrequired");
            logModuleCall("registrobr",$values["error"],$params);
			return $values;
		}
    }
  
    $RegistrantTaxIDDigits = preg_replace("/[^0-9]/","",$RegistrantTaxID);
    if (isCpfValid($RegistrantTaxIDDigits)==TRUE) {
		$RegistrantTaxID = substr($RegistrantTaxIDDigits,0,3).".".substr($RegistrantTaxIDDigits,3,3).".".substr($RegistrantTaxIDDigits,6,3)."-".substr($RegistrantTaxIDDigits,9,2);
    } 
    else {
        $RegistrantTaxID = substr($RegistrantTaxIDDigits,0,2).".".substr($RegistrantTaxIDDigits,2,3).".".substr($RegistrantTaxIDDigits,5,3)."/".substr($RegistrantTaxIDDigits,8,4)."-".substr($RegistrantTaxIDDigits,12,2);
    }
	
	$regperiod = $params["regperiod"];
	

	# Get registrant details
	$name = $params["original"]["firstname"]." ".$params["original"]["lastname"];
	
	if (isCpfValid($RegistrantTaxIDDigits)==TRUE) {
		$RegistrantOrgName = substr($RegistrantContactName,0,40);
	
	} else {
		$RegistrantOrgName = substr($params["original"]["companyname"],0,50);
		if (empty($RegistrantOrgName)) {
			$values['error'] = _registrobr_lang("companynamerequired");
			return $values;
		}
	}
	
	


	
	# Domain information and check provider
	
	$objRegistroEPPBrorg = RegistroEPPFactory::build('RegistroEPPBrorg');
	
	$objRegistroEPPBrorg->set('contactID',$RegistrantTaxID);
	$objRegistroEPPBrorg->set('contactIDDigits',$RegistrantTaxIDDigits);
	
	
	try {		
		$objRegistroEPPBrorg->login($moduleparams);
		$objRegistroEPPBrorg->getInfo(true);
		
		$coderes = $objRegistroEPPBrorg->get('coderes');
		
		if($coderes == '1000'){
			# If it's already on the database, verify new domains can be registered	
			$providerID = $objRegistroEPPBrorg->get('clID');
			$objRegistroEPPBrorg->verifyProvider($providerID,$moduleparams["Username"]);
		}
		else {
			# Company or individual not in the database, proceed to org contact creation
			
			
			$street1	= $params["original"]["address1"];
			$street2	= $params["original"]["address2"];
			$city 		= $params["original"]["city"];
			$sp			= _registrobr_StateProvince($params["original"]["state"]);
			$pc			= $params["original"]["postcode"];
			$cc			= $params["original"]["country"];
			$email		= $params["original"]["email"];
			$voice		= substr($params["original"]["fullphonenumber"],1);
						
			$objRegistroEPPBrorg->set('domain',$domain);			
			$objRegistroEPPBrorg->set('name',$name);
			$objRegistroEPPBrorg->set('street1',$street1);
			$objRegistroEPPBrorg->set('street2',$street2);
			$objRegistroEPPBrorg->set('street3',$street3);			
			$objRegistroEPPBrorg->set('city',$city);
			$objRegistroEPPBrorg->set('sp',$sp);
			$objRegistroEPPBrorg->set('pc',$pc);
			$objRegistroEPPBrorg->set('cc',$cc);
			$objRegistroEPPBrorg->set('voice',$voice);
			$objRegistroEPPBrorg->set('email',$email);
			
			$objRegistroEPPBrorg->createData();
			
			
			$idt = $objRegistroEPPBrorg->get('id');
			
			# Create Org
			$objRegistroEPPRegistrant = RegistroEPPFactory::build('RegistroEPPBrorg');
			$objRegistroEPPRegistrant->set('netClient',$objRegistroEPPBrorg->get('netClient'));
			$objRegistroEPPRegistrant->set('domain',$domain);
			$objRegistroEPPRegistrant->set('contactID',$RegistrantTaxID);
			$objRegistroEPPRegistrant->set('contactIDDigits',$RegistrantTaxIDDigits);
			$objRegistroEPPRegistrant->set('idt',$idt);
			
			$objRegistroEPPRegistrant->set('name',$name);
			$objRegistroEPPRegistrant->set('street1',$street1);
			$objRegistroEPPRegistrant->set('street2',$street2);
			$objRegistroEPPRegistrant->set('street3',$street3);
			
			$objRegistroEPPRegistrant->set('city',$city);
			$objRegistroEPPRegistrant->set('sp',$sp);
			$objRegistroEPPRegistrant->set('pc',$pc);
			$objRegistroEPPRegistrant->set('cc',$cc);
			$objRegistroEPPRegistrant->set('voice',$voice);
			$objRegistroEPPRegistrant->set('email',$email);
			
			$objRegistroEPPRegistrant->createOrgData();
		
				
							
		}
		
	}
	catch (Exception $e){
		$values["error"] = $e->getMessage();
		return $values;
	}
	

	
	##### Create domain
	
	
	$Nameservers["ns1"] = $params["ns1"];
	$Nameservers["ns2"] = $params["ns2"];
	$Nameservers["ns3"] = $params["ns3"];
	$Nameservers["ns4"] = $params["ns4"];
	$Nameservers["ns5"] = $params["ns5"];
	
	$objRegistroEPPNewDomain = RegistroEPPFactory::build('RegistroEPPDomain');
	$objRegistroEPPNewDomain->set('netClient',$objRegistroEPPBrorg->get('netClient'));
	
	$objRegistroEPPNewDomain->set('domain',$domain);
	$objRegistroEPPNewDomain->set('regperiod',$regperiod);
	$objRegistroEPPNewDomain->set('contactIDDigits',$RegistrantTaxIDDigits);
	$objRegistroEPPNewDomain->set('contactID',$RegistrantTaxID);
	$objRegistroEPPNewDomain->set('tech',$moduleparams['TechC']);
	
	try {
		$objRegistroEPPNewDomain->createDomain($Nameservers);
	}
	catch (Exception $e){
		$values["error"] = $e->getMessage();
		return $values;
	}
	
	
}
       
# Function to register domain


                                      
# Function to renew domain

function registrobr_RenewDomain($params) {
    
    # We need pear for the error handling
    require_once "PEAR.php";

	# Grab variables
	$tld = $params["tld"];
	$sld = $params["sld"];
	$regperiod = $params["regperiod"];

    # Get an EPP Connection                    
    $client = _registrobr_Client();
    # Create new EPP client
    if (PEAR::isError($client)) {
	return _registrobr_pear_error($client,'renewconnerror');
    }
    # Create new EPP client
                        
    $request='
            <epp xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:epp="urn:ietf:params:xml:ns:epp-1.0" 
            xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
                        <command>
                            <info>
                                <domain:info xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
                                    <domain:name hosts="all">'.$sld.'.'.$tld.'</domain:name>
                                </domain:info>
                            </info>
                            <clTRID>'.mt_rand().mt_rand().'</clTRID>
                        </command>
            </epp>
            ';

    $response = $client->request($request);
                                     
    # Parse XML result
    # Check results	
    $answer = _registrobr_parse_response($response);
    $coderes = $answer['coderes'];
    $msg = $answer['msg'];
    $reason = $answer['reason'];
    # Check results
    if($coderes != '1000') {
        return _registrobr_server_error('renewinfoerrorcode',$coderes,$msg,$reason,$request,$response);
    }
	# Sanitize expiry date
	$expdate = substr($doc->getElementsByTagName('exDate')->item(0)->nodeValue,0,10);

	# Send request to renew
	$request='
            <epp xmlns:epp="urn:ietf:params:xml:ns:epp-1.0" xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
                <command>
                    <renew>
                        <domain:renew>
                            <domain:name>'.$sld.'.'.$tld.'</domain:name>
                            <domain:curExpDate>'.$expdate.'</domain:curExpDate>
                            <domain:period unit="y">'.$regperiod.'</domain:period>
                        </domain:renew>
                    </renew>
                    <clTRID>'.mt_rand().mt_rand().'</clTRID>
                </command>
            </epp>
            ';
                                      
    $response = $client->request($request);
   
    # Check results	
    $answer = _registrobr_parse_response($response);
    $coderes = $answer['coderes'];
    $msg = $answer['msg'];
    $reason = $answer['reason'];
    # Check results

    if($coderes != '1000') {
		return _registrobr_server_error('renewerrorcode',$coderes,$msg,$reason,$request,$response);
    }
    return $values;

}

# Function to grab contact details

function registrobr_GetContactDetails($params) {

	# Include CPF and CNPJ stuff we need
	require_once 'isCnpjValid.php';
	require_once 'isCpfValid.php';

    require_once('RegistroEPP/RegistroEPPFactory.class.php');
    require_once('ParserResponse/ParserResponse.class.php');
    
    $domain = $params["sld"].".".$params["tld"];
    
    
    # Grab module parameters
    $moduleparams = getregistrarconfigoptions('registrobr');
    
    $objRegistroEPP = RegistroEPPFactory::build('RegistroEPPDomain');
    $objRegistroEPP->set('domain',$domain);
    
    try {
    	$objRegistroEPP->login($moduleparams);
    	$objRegistroEPP->getInfo();
    	$providerID = $objRegistroEPP->get('clID');
    	
       	$objRegistroEPP->verifyProvider($providerID,$moduleparams["Username"]);
 	}
    catch (Exception $e){
    	$values["error"] = $e->getMessage();
    	return $values;
    }
    
    $contacts = $objRegistroEPP->get('contacts');
    
    foreach ($contacts as $key => $value){
    	$Contacts[ucfirst($key)] = $value;
    }
    
    
    $RegistrantTaxID = $objRegistroEPP->get('organization');
    # Returned CNPJ has extra zero at left
    if(isCpfValid($RegistrantTaxID)!=TRUE) { 
    	$RegistrantTaxID=substr($RegistrantTaxID,1);
    };
    $RegistrantTaxIDDigits = preg_replace("/[^0-9]/","",$RegistrantTaxID);
    
    try {
	    #Get info about the brorg 
	    $objRegistroEPPBrorg = RegistroEPPFactory::build('RegistroEPPBrorg');
	    $objRegistroEPPBrorg->set('netClient',$objRegistroEPP->get('netClient'));
	    $objRegistroEPPBrorg->set('domain',$domain);
	    $objRegistroEPPBrorg->set('contactID',$RegistrantTaxID);
	    $objRegistroEPPBrorg->set('contactIDDigits',$RegistrantTaxIDDigits);
	    $objRegistroEPPBrorg->getInfo();
    }
    catch(Exception $e){
    	$values["error"] = $e->getMessage();
    	return $values;
    }
    $Contacts["Registrant"]= $objRegistroEPPBrorg->get('contact');
    
    $Name = $objRegistroEPPBrorg->get('name');
	#Get Info about the brorg
    
    # Companies have both company name and contact name, individuals only have their own name 
    if (isCnpjValid($RegistrantTaxIDDigits)==TRUE) {
        $values["Registrant"][_registrobr_lang("companynamefield")] = $Name;
    }
    else { 
    	$values["Registrant"][_registrobr_lang("fullnamefield")] = $Name;
    }
    
    #Get Org, Adm and Tech Contacts
    
    foreach ($Contacts as $key => $value) {
    	
    	if($key == 'Billing') continue;
    	
		try {
	    	$objRegistroEPPBrorg->set('contactID','');
	    	$objRegistroEPPBrorg->set('contactIDDigits',$value);
			$objRegistroEPPBrorg->getInfo();
		}
		catch(Exception $e){
			$values["error"] = $e->getMessage();
			return $values;
		}
		
		$values[$key][_registrobr_lang("fullnamefield")] = $objRegistroEPPBrorg->get('name');
		$values[$key][_registrobr_lang("streetnamefield")] = $objRegistroEPPBrorg->get('street1');
		$values[$key][_registrobr_lang("streetnumberfield")] = $objRegistroEPPBrorg->get('street2');
		$values[$key][_registrobr_lang("addresscomplementsfield")] = $objRegistroEPPBrorg->get('street3');
		$values[$key][_registrobr_lang("citynamefield")] = $objRegistroEPPBrorg->get('city');
		$values[$key][_registrobr_lang("stateprovincefield")] = $objRegistroEPPBrorg->get('sp');
		$values[$key][_registrobr_lang("zipcodefield")] = $objRegistroEPPBrorg->get('pc');
		$values[$key][_registrobr_lang("countrycodefield")] = $objRegistroEPPBrorg->get('cc');
		$values[$key][_registrobr_lang("phonenumberfield")] = $objRegistroEPPBrorg->get('voice');
		$values[$key]["Email"] = $objRegistroEPPBrorg->get('email');
 	
	}        
 	return $values;
}

# Function to save contact details

function registrobr_SaveContactDetails($params) {
	
	

    # If nothing was changed, return
    if ($params["contactdetails"]==$params["original"]["contactdetails"]) {
        $values=array();
        return $values;
    }
    
    # Include CPF and CNPJ stuff we need
    require_once 'isCnpjValid.php';
    require_once 'isCpfValid.php';
    
    require_once('RegistroEPP/RegistroEPPFactory.class.php');
    require_once('ParserResponse/ParserResponse.class.php');
    
    $domain = $params["original"]["sld"].".".$params["original"]["tld"];
    //must be used the original info  
    
    # Grab module parameters
    $moduleparams = getregistrarconfigoptions('registrobr');
    
	$objRegistroEPP = RegistroEPPFactory::build('RegistroEPPDomain');
	$objRegistroEPP->set('domain',$domain);
	    
	try {
		$objRegistroEPP->login($moduleparams);
		$objRegistroEPP->getInfo();
		$providerID = $objRegistroEPP->get('clID');
		$objRegistroEPP->verifyProvider($providerID,$moduleparams["Username"]);
	}
	catch (Exception $e){
		$values["error"] = $e->getMessage();
		return $values;
	}

	
	$contacts = $objRegistroEPP->get('contacts');
	
	$RegistrantTaxID = $objRegistroEPP->get('organization');
	
	foreach ($contacts as $key => $value){
		$Contacts[ucfirst($key)] = $value;
	}
	
	# Returned CNPJ has extra zero at left
	if(isCpfValid($RegistrantTaxID)!=TRUE) {
		$RegistrantTaxID=substr($RegistrantTaxID,1);
	};
	
	$RegistrantTaxIDDigits = preg_replace("/[^0-9]/","",$RegistrantTaxID);
	
	try {
		#Get info about the brorg
		$objRegistroEPPBrorg = RegistroEPPFactory::build('RegistroEPPBrorg');
		$objRegistroEPPBrorg->set('netClient',$objRegistroEPP->get('netClient'));
		$objRegistroEPPBrorg->set('domain',$domain);
		$objRegistroEPPBrorg->set('contactID',$RegistrantTaxID);
		$objRegistroEPPBrorg->set('contactIDDigits',$RegistrantTaxIDDigits);
		$objRegistroEPPBrorg->getInfo();
	}
	catch(Exception $e) {
		$values["error"] = $e->getMessage();
		return $values;
	}
	$Contacts["Registrant"]= $objRegistroEPPBrorg->get('contact');
	
	$Name = $objRegistroEPPBrorg->get('name');
	#Get Info about the brorg
	
	# Companies have both company name and contact name, individuals only have their own name
	if (isCnpjValid($RegistrantTaxIDDigits)==TRUE) {
		$values["Registrant"][_registrobr_lang("companynamefield")] = $Name;
	}
	else {
		$values["Registrant"][_registrobr_lang("fullnamefield")] = $Name;
	}
	
	####################

    # This flag will signal the need for doing a domain update or not
    $DomainUpdate = FALSE ; 

    # This flag will signal the need for doing a brorg update or not
    $OrgUpdate = FALSE ;
    
    # Verify which contacts need updating
    $ContactTypes = array ("Registrant","Admin","Tech");
    $NewContactsID = array();
    $objNewContacts = array();
	
	foreach ($ContactTypes as $type)  {
		/*
		[Full Name] => Flaavio Toccos Yanaica
		[Street Name] => Av. Nacoes Unidas. 333
		[Street Number] => 1111
		[Address Complements] => 2222
		[City] => Sao Paulo
		[State or Province] => SP
		[Zip code] => 03182-040
		[Country] => BR
		[Phone] => +55.38183343
		[Email] => fkyanai7@gmail.com
		)
		*/
		$cdetails = $params["contactdetails"][$type];
		
		$name = !empty($cdetails["Full Name"]) ? $cdetails["Full Name"] : '';
		$street1 = !empty($cdetails["Street Name"]) ? $cdetails["Street Name"] : '';
		$street2 = !empty($cdetails["Street Number"]) ? $cdetails["Street Number"] : '';
		$street3 = !empty($cdetails["Address Complements"]) ? $cdetails["Address Complements"] : '';
		$city = !empty($cdetails["City"]) ? $cdetails["City"] : '';
		$sp = !empty($cdetails["State or Province"]) ? $cdetails["State or Province"] : '';
		$pc = !empty($cdetails["Zip code"]) ? $cdetails["Zip code"] : '';
		$cc = !empty($cdetails["Country"]) ? $cdetails["Country"] : '';
		$voice = !empty($cdetails["Phone"]) ? $cdetails["Phone"] : '';
		$email = !empty($cdetails["Email"]) ? $cdetails["Email"] : '';

		$objRegistroEPPBrorg = RegistroEPPFactory::build('RegistroEPPBrorg');
		$objRegistroEPPBrorg->set('netClient',$objRegistroEPP->get('netClient'));
		$objRegistroEPPBrorg->set('domain',$domain);
		$objRegistroEPPBrorg->set('contactID',$RegistrantTaxID);
		$objRegistroEPPBrorg->set('contactIDDigits',$RegistrantTaxIDDigits);
		
		$objRegistroEPPBrorg->set('name',$name);
		$objRegistroEPPBrorg->set('street1',$street1);
		$objRegistroEPPBrorg->set('street2',$street2);
		$objRegistroEPPBrorg->set('street3',$street3);
		
		$objRegistroEPPBrorg->set('city',$city);
		$objRegistroEPPBrorg->set('sp',$sp);
		$objRegistroEPPBrorg->set('pc',$pc);
		$objRegistroEPPBrorg->set('cc',$cc);
		$objRegistroEPPBrorg->set('voice',$voice);
		$objRegistroEPPBrorg->set('email',$email);
				
		try {
			$objRegistroEPPBrorg->createData();
		}
		catch (Exception $e){
			$values["error"] = $e->getMessage();
			return $values;
		}
		$NewContactsID[$type] = $objRegistroEPPBrorg->get('id');
		$objNewContacts[$type] = $objRegistroEPPBrorg;
		
		if ($type!="Registrant") {
			$DomainUpdate=TRUE;
		}
		else {
			$OrgUpdate=TRUE;
			//$OrgContactXML=$request;
		}
		
	}

    if ($DomainUpdate == TRUE) {
		$NewContactsID["Billing"] = $NewContactsID["Admin"];

		try {
			//obj Domain
			$objRegistroEPP->updateInfo($Contacts,$NewContactsID);
				
		}
		catch(Exception $e){
			$values["error"] = $e->getMessage();
			return $values;
		}
    	
    }
    
    if ($OrgUpdate == TRUE){ 
    	try {
    		#Get info about the brorg
    		$objRegistroEPPBrorg = RegistroEPPFactory::build('RegistroEPPBrorg');
    		$objRegistroEPPBrorg->set('netClient',$objRegistroEPP->get('netClient'));
    		$objRegistroEPPBrorg->set('domain',$domain);
    		$objRegistroEPPBrorg->set('contactID',$RegistrantTaxID);
    		$objRegistroEPPBrorg->set('contactIDDigits',$RegistrantTaxIDDigits);
    		$objRegistroEPPBrorg->getInfo();
    	}
    	catch(Exception $e) {
    		$values["error"] = $e->getMessage();
    		return $values;
    	}
    	//Get current org contact
    	$Contacts["Registrant"]= $objRegistroEPPBrorg->get('contact');
    	
    	if (isCpfValid($RegistrantTaxIDDigits)==TRUE) {
    		$companyname = $objRegistroEPPBrorg->get('name');
    	}
    	else { 
    		$companyname =( empty($params["contactdetails"]["Registrant"][_registrobr_lang("companynamefield")]) ? $params["contactdetails"]["Registrant"]["Company Name"] : $params["contactdetails"]["Registrant"][_registrobr_lang("companynamefield")]);
    	}
    	
    	if (isCnpjValid($RegistrantTaxIDDigits)) {
    		$responsible = $objRegistroEPPBrorg->get('name');
    	}
    	
    	
    	$objReg = $objNewContacts["Registrant"];

    	$objNewRegistroEPPBrorg = RegistroEPPFactory::build('RegistroEPPBrorg'); 
    	$objNewRegistroEPPBrorg->set('netClient',$objRegistroEPP->get('netClient'));
    	$objNewRegistroEPPBrorg->set('domain',$domain);
    	$objNewRegistroEPPBrorg->set('contactID',$RegistrantTaxID);
    	$objNewRegistroEPPBrorg->set('contactIDDigits',$RegistrantTaxIDDigits);
    	
    	
    	$objNewRegistroEPPBrorg->set('name',$objReg->get('name'));
    	$objNewRegistroEPPBrorg->set('street1',$objReg->get('street1'));
    	$objNewRegistroEPPBrorg->set('street2',$objReg->get('street2'));
    	$objNewRegistroEPPBrorg->set('street3',$objReg->get('street3'));
    	$objNewRegistroEPPBrorg->set('city',$objReg->get('city'));
    	$objNewRegistroEPPBrorg->set('sp',$objReg->get('sp'));
    	$objNewRegistroEPPBrorg->set('pc',$objReg->get('pc'));
    	$objNewRegistroEPPBrorg->set('cc',$objReg->get('cc'));
    	$objNewRegistroEPPBrorg->set('voice',$objReg->get('voice'));
    	$objNewRegistroEPPBrorg->set('email',$objReg->get('email'));
    	$objNewRegistroEPPBrorg->set('responsible',$objReg->get('name'));
    	 
    	
    	try {
    		$objNewRegistroEPPBrorg->updateInfo($Contacts,$NewContactsID);
    	}
    	catch(Exception $e){
    		$values["error"] = $e->getMessage();
    		return $values;
    	}  	     	 
    }

    $values = array();
    
    return $values;
}

# Domain Delete (used in .br only for Add Grace Period)
function registrobr_RequestDelete($params) {
	require_once('RegistroEPP/RegistroEPPFactory.class.php');
	
	$domain = $params["sld"].".".$params["tld"];
	
	# Grab module parameters
	$moduleparams = getregistrarconfigoptions('registrobr');
	
	$objRegistroEPPDomain = RegistroEPPFactory::build('RegistroEPPDomain');
	$objRegistroEPPDomain->set('domain',$domain);
	
	try {
		$objRegistroEPPDomain->login($moduleparams);
		$objRegistroEPPDomain->deleteDomain();
		
		$coderes = $objRegistroEPPDomain->get('coderes');
	}
	catch (Exception $e){
		$values["error"] = $e->getMessage();
		return $values;
	}
	
	if($coderes == '2303') {
			$values = registrobr_Getnameservers($params);
		
			# If no error, domain is still a ticket, so we remove the nameservers to prevent it becoming a domain
			if (empty($values["error"])) {
				$setparams=$params;
				$setparams["ns1"]='';
				$setparams["ns2"]='';
				$setparams["ns3"]='';
				$setparams["ns4"]='';
				$setparams["ns5"]='';
			
				$values = registrobr_SaveNameservers($setparams);
				if (empty($values["error"])) {
					$values=array();
					return $values ;
				}
			}
	}

}



function registrobr_Sync($params) {
    
    # We need pear for the error handling
    require_once "PEAR.php";
    
    # Get an EPP connection
    $client = _registrobr_Client();
    if (PEAR::isError($client)) {
        return _registrobr_pear_error($client,'syncconnerror');
    }
    
    #For every domain sync, also do a poll queue clean
    _registrobr_Poll($client);
    
    #Request a sync for the specified domain
    $values = _registrobr_SyncRequest($client,$params);
    return $values;
}
    
function _registrobr_SyncRequest($client,$params) {

    # Grab variables
    $domain = $params['domain'];
    $domainid = $params['domainid'];
    $moduleparams = getregistrarconfigoptions('registrobr');
    $table = "mod_registrobr";
    $fields = "clID,domainid,domain,ticket";
    $where = array("clID"=>$moduleparams['Username'],"domainid"=>$domainid,"domain"=>$domain);
    $result = select_query($table,$fields,$where);
    $data = mysql_fetch_array($result);
    $ticket = $data['ticket'];
    
    #Initialize return values
    $values=array();
    
    if(empty($ticket)) {
        $values["error"]=_registrobr_lang("syncdomainnevercreated");
        return $values;
    }

    $request = '
            <epp xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:epp="urn:ietf:params:xml:ns:epp-1.0"
            xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
                <command>
                    <info>
                        <domain:info xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
                            <domain:name hosts="all">'.$domain.'</domain:name>
                        </domain:info>
                    </info>
                    <clTRID>'.mt_rand().mt_rand().'</clTRID>
                </command>
            </epp>
            ';
    
    $response = $client->request($request);
    
	# Parse XML result		
        # Check results	
	$answer = _registrobr_parse_response($response);
        $coderes = $answer['coderes'];
        $msg = $answer['msg'];
        $reason = $answer['reason'];
	$contact = $answer['contact'];
	# Check results
    
    # Check if result is ok
	if($coderes != '1000') {
        	if ($coderes != '2303') {
			return _registrobr_server_error('syncerrorcode',$coderes,$msg,$reason,$request,$response);
        	}
        
    # See if domain not found is due to domain still being a ticket
    $request = '
            <epp xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:epp="urn:ietf:params:xml:ns:epp-1.0"
            xmlns:domain="urn:ietf:params:xml:ns:domain-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
                <command>
                    <info>
                        <domain:info xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
                            <domain:name hosts="all">'.$domain.'</domain:name>
                        </domain:info>
                    </info>
                    <extension>
                        <brdomain:info xmlns:brdomain="urn:ietf:params:xml:ns:brdomain-1.0"
                        xsi:schemaLocation="urn:ietf:params:xml:ns:brdomain-1.0
                        brdomain-1.0.xsd">
                            <brdomain:ticketNumber>'.$ticket.'</brdomain:ticketNumber>
                        </brdomain:info>
                    </extension>
                    <clTRID>'.mt_rand().mt_rand().'</clTRID>
                </command>
            </epp>
            ';
        
    $response = $client->request($request);
        
    # Check results	
	$answer = _registrobr_parse_response($response);
        $coderes = $answer['coderes'];
        $msg = $answer['msg'];
        $reason = $answer['reason'];
	$contact = $answer['contact'];
	
    # Check results
    if($coderes == '1000') {
            #Guess: no info equals pending
            return $values;
    }
        
    if ($coderes != '2303') {
		return _registrobr_server_error('syncerrorcode',$coderes,$msg,$reason,$request,$response);
    }
    
    $values["error"] = _registrobr_lang('Domain').$domain._registrobr_lang('syncdomainnotfound');
    return $values;
    }
    
    $doc=$answer['doc'];
    $createdate = substr($doc->getElementsByTagName('crDate')->item(0)->nodeValue,0,10);
    $values['registrationdate'] = $createdate;
    $nextduedate = substr($doc->getElementsByTagName('exDate')->item(0)->nodeValue,0,10);
    $holdreasons = $doc->getElementsByTagName('onHoldReason');
    
    #if ticket number is different, this is actually a new domain with the same name
    if ($doc->getElementsByTagName('ticketNumber')->item(0)->nodeValue!=$ticket) {
        $values['expired'] = true ;
        $values['expirydate'] = $createdate;
    } elseif (!empty($holdreasons)) {
        if (array_search("billing",$holdreasons)!=FALSE) {
            $values['expired'] = true;
            $values['expirydate'] = $nextduedate;
        }
    } else {
        $values['active'] = true;
        $values['expirydate'] = $nextduedate;
        
    }
    return $values;
}

function _registrobr_Poll($client) {
          
  
    
    # We need pear for the error handling
    require_once "PEAR.php";
    
    # We need XML beautifier for showing understable XML code
    require_once dirname(__FILE__) . '/BeautyXML.class.php';
    
    
    # We need EPP stuff
    
    require_once dirname(__FILE__) . '/Net/EPP/Frame.php';
    require_once dirname(__FILE__) . '/Net/EPP/Frame/Command.php';
    require_once dirname(__FILE__) . '/Net/EPP/ObjectSpec.php';
    
    # Get module parameters
    $moduleparams = getregistrarconfigoptions('registrobr');
    
   
    
    # Loop with message queue
    while (!$last) {
          
        # Request messages
        $request = '
                    <epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                    xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
                        <command>
                            <poll op="req"/>
                            <clTRID>'.mt_rand().mt_rand().'</clTRID>
                        </command>
                    </epp>
                    ';
        $response = $client->request($request);
          
        # Decode response
        
        $answer = _registrobr_parse_response($response);
        $coderes = $answer['coderes'];
        $msg = $answer['msg'];
        $reason = $answer['reason'];
        $contact = $answer['contact'];
        $doc = $answer['doc'];
        
        # Check results
        
        # This is the last one
        if ($coderes == 1300) {
            $last = 1;
        } else  {
            $msgid = $doc->getElementsByTagName('msgQ')->item(0)->getAttribute('id');
            $content = _registrobr_lang("Date").substr($doc->getElementsByTagName('qDate')->item(0)->nodeValue,0,10)." ";
            $content .= _registrobr_lang("Time").substr($doc->getElementsByTagName('qDate')->item(0)->nodeValue,11,10)." UTC\n";
            $code = $doc->getElementsByTagName('code')->item(0)->nodeValue;
            $content .= _registrobr_lang("Code").$code."\n";
            $content .= _registrobr_lang("Text").$doc->getElementsByTagName('txt')->item(0)->nodeValue."\n";
            $reason = $doc->getElementsByTagName('reason');
            if (!empty($reason)) $content .= _registrobr_lang("Reason").$doc->getElementsByTagName('reason')->item(0)->nodeValue."\n";
            $content .= _registrobr_lang("FullXMLBelow");
            $bc = new BeautyXML();
            
            $content .= htmlentities($bc->format($response));
            
            $ticket='';
            $domain='';
            $taxpayerID='';
            
            switch($code) {
                case '1': case '22': case '28': case '29':
                    $ticket = $doc->getElementsByTagName('ticketNumber')->item(0)->nodeValue;
                    
                    #no break, poll messages with ticketNumber also have domain in objectId
                    
                case '2': case '3': case '4': case '5': case '6': case '7': case '8': case '9':
                case '10': case '11': case '12': case '13': case '14': case '15': case '16': case '17': case '18':
                case '20':
                case '107': case '108':
                case '304': case '305':
                    
                    $domain = $doc->getElementsByTagName('objectId')->item(0)->nodeValue;
                    break;
                
                case '100': case '101': case '102': case '103': case '106':
                    
                    $taxpayerID = $doc->getElementsByTagName('objectId')->item(0)->nodeValue;
                    break;
            }
            
            $taxpayerID=preg_replace("/[^0-9]/","",$taxpayerID);
            
            if (in_array($code,array('300','302','303','305'))==TRUE) {
                            $issue["priority"] = "High";
                            $issue["deptid"] = $moduleparams["FinanceDept"];
            } elseif (in_array($code,array('301','304'))==TRUE) {
                            $issue["priority"] = "Low";
                            $issue["deptid"] = $moduleparams["FinanceDept"];
            }
            else {
                            $issue["priority"] = "Low" ;
                            $issue["deptid"] = $moduleparams["TechDept"];
                
            }
                    
            
            $issue["clientid"]=0;
            
            if (!empty($domain)) {
               
                $issue["domain"] =$domain;
                
                if (empty($ticket)) {
                    $queryresult = mysql_query("SELECT domainid FROM mod_registrobr WHERE clID='".$moduleparams['Username']." domain='".$domain."'");
                    $data = mysql_fetch_array($queryresult);
                    
                    # if there is only one domain with this name, we can match it to a domainid without a ticket
                    if (count($data)==1) {
                        $domainid = $data['domainid'];
                    }
                } else {
                    $queryresult = mysql_query("SELECT domainid FROM mod_registrobr WHERE clID='".$moduleparams['Username']." ticket='".$ticket."'");
                    $data = mysql_fetch_array($queryresult);
                    $domainid = $data['domainid'];
                }
                if (!empty($domainid)) {
                    $issue["domainid"] = $domainid;
                    $queryresult = mysql_query("SELECT userid FROM tbldomains WHERE id='".$domainid."'");
                    $data = mysql_fetch_array($queryresult);
                    $issue["clientid"]=$data['userid'];

                }
            }
            
            if (!empty($taxpayerID)&&($issue["clientid"]==0)) {
                $issue["clientid"] = "1";
                
            }
        
        
        $issue["subject"] = _registrobr_lang("Pollmsg");
        $issue["message"] = $content;
        $user = $moduleparams['Sender'];
        $queryresult = mysql_query("SELECT firstname,lastname,email FROM tbladmins WHERE username = '".$user."'");
        $data = mysql_fetch_array($queryresult);
                                         
        
        $issue["name"] = $data["firstname"]." ".$data["lasttname"];
        $issue["email"] = $data["email"];
            
            
        $results = localAPI("openticket",$issue,$user);
        if ($results['result']!="success") {
                logModuleCall("registrobr",_registrobr_lang("epppollerror"),$issue,$results);
                return;
            }
        

            
        # Ack poll message
        $request='  <epp xmlns="urn:ietf:params:xml:ns:epp-1.0" 
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                    xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd"> 
                        <command>
                            <poll op="ack" msgID="'.$msgid.'"/>
                            <clTRID>'.mt_rand().mt_rand().'</clTRID>
                        </command>
                    </epp>
                    ';
        $response = $client->request($request);

        # Decipher XML
        # Check results	
	$answer = _registrobr_parse_response($response);
        $coderes = $answer['coderes'];
        $msg = $answer['msg'];
        $reason = $answer['reason'];
	$contact = $answer['contact'];
	# Check results
        


        # Check result
        if($coderes != '1000') {
		return _registrobr_server_error('pollackerrorcode',$coderes,$msg,$reason,$request,$response);
        }
    
    #brace below close msg if
    }

    #brace below close while(!last) loop
    }
        
    return;
}



# Function to create internal .br EPP request

function _registrobr_Client() {

	$moduleparams = getregistrarconfigoptions('registrobr');
	
	

	

	$requestXML = $objRegistroEPP->xml();    
	$responseXML = $client->request($requestXML);
	$objParser = New ParserResponse();
	$objParser->parse($responseXML);
		
	$coderes = $objParser->get('coderes');

	if ($coderes != '1000') {
		return $objRegistroEPP->errorEPP('epplogin',$objParser,$requestXML,$responseXML,$language);
	}
    return $client;
}

    
function _registrobr_normaliza($string) {
        
    $string = str_replace('&nbsp;',' ',$string);
    $string = trim($string);
    $string = html_entity_decode($string,ENT_QUOTES,'UTF-8');
        
    //Instead of The Normalizer class ... requires (PHP 5 >= 5.3.0, PECL intl >= 1.0.0)
    $normalized_chars = array( 'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f', ' ' => '');
    
    $string = strtr($string,$normalized_chars);
    $string = strtolower($string);
    return $string;
}
    
function _registrobr_StateProvince($sp) {
        
    if (strlen($sp)==2) return $sp;
    $estado = _registrobr_normaliza($sp);
    $map = array(
                "acre" => "AC",
                "alagoas" => "AL",
                "amazonas" => "AM",
                "amapa" => "AP",
                "bahia" => "BA",
                "baia" => "BA",
                "ceara" => "CE",
                "distritofederal" => "DF",
                "espiritosanto" => "ES",
                "espiritusanto" => "ES",
                "goias" => "GO",
                "goia" => "GO",
                "maranhao" => "MA",
                "matogrosso" => "MT",
                "matogroso" => "MT",
                "matogrossodosul" => "MS",
                "matogrossosul" => "MS",
                "matogrossodesul" => "MS",
                "minasgerais" => "MG",
                "minasgeral" => "MG",
                "para" => "PA",
                "paraiba" => "PB",
                "parana" => "PR",
                "pernambuco" => "PE",
                "pernanbuco" => "PE",
                "piaui" => "PI",
                "riodejaneiro" => "RJ",
                "rio" => "RJ",
                "riograndedonorte" => "RN",
                "riograndenorte" => "RN",
                "rondonia" => "RO",
                "riograndedosul" => "RS",
                "riograndedesul" => "RS",
                "riograndesul" => "RS",
                "roraima" => "RR",
                "santacatarina" => "SC",
                "sergipe" => "SE",
                "saopaulo" => "SP",
                "tocantins" => "TO"
                );
			    if(!empty($map[$estado])){
					return $map[$estado];
			    }
			    else {
			    	return $sp;
			    }
    }
                            

function _registrobr_identify_env_encode() {
	#Encoding default UTF-8


	if(!empty($CONFIG['Charset'])){
                
		return $CONFIG['Charset'];
	}
	else {
    		$table = "tblconfiguration";
    		$fields = "Charset";
    		$where = array();
    		$result = select_query($table,$fields,$where);
    		$data = mysql_fetch_array($result);

    		if($data['Charset']) {
			return $data['Charset'];
		}
		else {
			return 'UTF-8';
		}
	}

}

#Aux functions

#Pear error

function _registrobr_pear_error($client,$strerror){
	$client = _registrobr_set_encode($client);
	$values["error"]=_registrobr_lang($strerror).$client;
	logModuleCall("registrobr",$values["error"]);
	return $values;
}

#Parse xml response from epp server
function _registrobr_parse_response($response){

	$doc= new DOMDocument();
	$doc->loadXML($response);
	$atts = array();
	$atts['coderes'] = $doc->getElementsByTagName('result')->item(0)->getAttribute('code');
	$atts['msg'] = $doc->getElementsByTagName('msg')->item(0)->nodeValue;
	$atts['reason'] = $doc->getElementsByTagName('reason')->item(0)->nodeValue;
	$atts['id'] = $doc->getElementsByTagName('id')->item(0)->nodeValue;
	$atts['contact'] = $doc->getElementsByTagName('contact')->item(0)->nodeValue;
	$atts['doc'] = $doc;

	return $atts;
}
#registro.br response error

function _registrobr_server_error($strerror,$coderes,$msg,$reason,$request,$response){

	$msg = _registrobr_set_encode($msg);
	$errormsg = _registrobr_lang($strerror).$coderes._registrobr_lang('msg').$msg."'";
	if (!empty($reason)) {
		$reason = _registrobr_set_encode($reason);
		$errormsg.= _registrobr_lang("reason").$reason."'";
	};
	logModuleCall("registrobr",$errormsg,$request,$response);
	$values["error"] = $errormsg;
	return $values;
}


function _registrobr_convert_to_punycode($string){

	# Setup include dir
	$include_path = ROOTDIR . '/modules/registrars/registrobr';
	set_include_path($include_path . PATH_SEPARATOR . get_include_path());
	
	require_once('Idna/idna_convert.class.php');
		
	$IDN = new idna_convert(array('idn_version' => '2008'));
	
	$encoded = $IDN->encode($string);
	
	return $encoded;
	
}
function _registrobr_detect_encode($text){
	$current_encoding = mb_detect_encoding($text, 'auto');
	if(empty($current_encoding)){
		return 'UTF-8';
	}
	else {
		return $current_encoding;
	}
	
}

function _registrobr_set_encode($text,$encode) {
	
	$current_encoding = _registrobr_detect_encode($text);
    if(empty($encode)){
    	$to_encode = _registrobr_identify_env_encode();
    }
    else {
    	$to_encode = $encode."//TRANSLIT";
    }

    $text = iconv($current_encoding, $to_encode, $text);
    return $text;
}

function _registrobr_lang($msgid) {

    # Grab module parameters
    $moduleparams = getregistrarconfigoptions('registrobr');
    $msgs = array (
                    "epplogin" => array ("Erro no login EPP código ","EPP login error code "),
                    "msg" => array (" mensagem '"," message '"),
                    "reason" => array (" motivo '"," reason '"),
                    "eppconnect" => array ("Erro de conexão EPP","EPP connect error"),
                    "configerr" => array ("Erro nas opções de configuração","Config options errorr"),
                    "specifypath" => array ("Favor informar o caminho para o arquivo de certificado","Please specifity path to certificate file"),
                    "invalidpath" => array ("Caminho para o arquivo de certificado inválido", "Invalid certificate file path"),
                    "specifypassphrase" => array ("Favor especificar a frase secreta do certificado", "Please specifity certificate passphrase"),
                    "deleteerrorcode" => array ("Erro na remoção de domíenio código ","Domain delete: error code "),
                    "deleteconnerror" => array ("Falha na conexão EPP ao tentar remover domínio erro ","Domain delete: EPP connection error "),
                    "getnsconnerror" => array ("Falha na conexão EPP ao tentar obter servidores DNS erro ", "get nameservers: EPP connection error "),
                    "setnsconnerror" => array ("Falha na conexão EPP ao tentar alterar servidores DNS erro ", "set nameservers: EPP connection error "),
                    "setnsgeterrorcode" => array ("Falha ao tentar obter servidores DNS atuais para alterar servidores DNS código ", "set nameservers: error getting nameservers code "),
                    "setnsupdateerrorcode" => array ("Falha ao alterar servidores DNS código ","set nameservers: update servers error code "),
                    "cpfcnpjrequired" => array ("Registro de domínios .br requer CPF ou CNPJ","register domain: .br registrations require valid CPF or CNPJ"),
                    "companynamerequired" => array ("Registros com CNPJ requerem nome da empresa preenchido",".br registrations with CNPJ require Company Name to be filled in"),
                    "registerconnerror" => array ("Falha na conexão EPP ao tentar registrar domínio erro ", "register domain: EPP connection error "),
                    "notallowed" => array ("Entidade só pode registrar domínios por provedor atualmente designado.", "entity can only register domains through designated registrar."),
                    "registergetorgerrorcode" => array ("Falha ao obter status de entidade para registrar domínio erro ","register domain: get org status error code "),
                    "registercreateorgcontacterrorcode" => array ("Falha ao criar contato para entidade erro ","register domain: create org contact error code "),
                    "registercreateorgerrorcode" => array ("Falha ao criar entidade para registrar domínio erro ","register domain: create org error code "),
                    "registererrorcode" => array ("Falha ao registrar domínio erro ","register domain error code "),
                    "renewconnerror" => array ("Falha na conexão EPP ao renovar domínio erro ", "renew domain: EPP connection error "),
                    "renewinfoerrorcode" => array ("Falha ao obter informações de domínio ao renovar domínio erro ", "renew: domain info error code "),
                    "renewerrorcode" => array ("Falha ao renovar domínio erro ","domain renew: error code "),
                    "getcontactconnerror" => array ("Falha na conexão EPP ao obter dados de contato erro ","get contact details: EPP connection error "), 
                    "getcontacterrorcode" => array ("Falha ao obter dados de contato erro ", "get contact details: domain info error code "),
                    "getcontactnotallowed" => array ("Somente provedor designado pode obter dados deste domínio.","get contact details: domain is not designated to this registrar."),
                    "getcontactorginfoerrorcode" => array ("Falha ao obter informações de entidade detentora de domínio erro ","get contact details: organization info error code "),
                    "getcontacttypeerrorcode" => array ("Falha ao obter dados de contato do tipo ","get contact details: "),
                    "getcontacterrorcode" => array ("código de erro ","contact info error code "),
                    "savecontactconnerror" => array ("Falha na conexão EPP ao gravar contatos erro ", "save contact details: EPP connection error "),
                    "savecontactdomaininfoerrorcode" => array ("Falha ao obter dados de domínio para gravar contatos erro ","set contact details: domain info error code"),
                    "savecontactnotalloweed" => array ("Somente provedor designado pode alterar dados deste domínio.", "Set contact details: domain is not designated to this registrar."),
                    "savecontacttypeerrorcode" => array ("Falha ao criar novo contato do tipo ","save contact details: "),
                    "savecontacterrorcode" => array ("código de erro ","contact create error code "),
                    "savecontactdomainupdateerrorcode" => array ("Falha ao atualizar domínio ao modificar contatos erro ","set contact: domain update error code "),
                    "savecontactorginfoeerrorcode" => array ("Falha de obtenção de informações de entidade ao modificar contatos erro ","set contact: org info error code "),
                    "savecontactorgupdateerrorcode" => array ("Falha ao atualizar entidade ao modificar contatos erro ","set contact: org update error code "),
                    "domainnotfound" => array ("Domínio ainda não registrado.","Domain not yet registered"),
                    "getnserrorcode" => array ("Falha ao obter dados de domínio erro ","get nameserver error code "),
                    "syncconnerror" => array ("Falha na conexão EPP ao sincronizar domínio erro ","domain sync: EPP connection error "),
                    "syncerrorcode" => array ("Falha ao tentar obter informação de domínio código ", "domain sync: error getting domain info code "),
                    "syncdomainnotfound" => array ("não mais registrado."," no longer registered"),
                    "syncdomainunknownstatus" => array(" apresentou status desconhecido: ","domain sync: unknown status code "),
                    "Domain" => array ("Domínio ","Domain "),
                    "domain" => array ("domínio ","domain "),
                    "syncreport" => array("Relatorio de Sincronismo de Dominios Registro.br\n","Registro.br Domain Sync Report\n"),
                    "syncreportdashes" => array ("------------------------------------------------\n","------------------------------\n"),
                    "ERROR" => array ("ERRO: ","ERROR: "),
                    "domainstatusok" => array ("Ativo","Active"),
                    "domainstatusserverhold" => array ("CONGELADO","PENDING"),
                    "domainstatusexpired" => array ("Vencido","Expired"),
                    "is" => array (" está "," is "),
                    "registration" => array ("(Criação: ","(Registered: "),
                    "epppollerror" => array ("Erro de ao fazer EPP Poll","EPP Polling error"),
                    "Pollmsg" => array ("Mensagem de Poll relativa a dominios .br","Poll message about .br domains"),
                    "pollackerrorcode" => array ("Falha ao dar recebimento de mensagem EPP Poll codigo ", "EPP Poll: error acknowledging a message error code "),
                    "Date" => array ("Data ","Date "),
                   "time" => array ("hora ","time "),
                   "Code" => array ("Codigo ", "code "),
                   "Text" => array ("Texto ","Text "),
                   "FullXMLBelow" => array ("Mensagem XML completo abaixo:\n","Full XML message below:\n"),
                       
                    "companynamefield" => array ("Razao Social","Company Name"),
                    "fullnamefield" => array ("Nome e Sobrenome","Full Name"),
                    "streetnamefield" => array ("Logradouro","Street Name"),
                    "streetnumberfield" => array ("Numero", "Street Number"),
                    "addresscomplementsfield" => array ("Complemento", "Address Complements"),
                    "citynamefield" => array ("Cidade","City"),
                    "stateprovincefield" => array ("Estado","State or Province"),
                    "zipcodefield" => array ("CEP","Zip code"),
                    "countrycodefield" => array ("Pais","Country"),
                    "phonenumberfield" => array ("Fone","Phone"),
                    );                   
         
    $langmsg = ($moduleparams["Language"]=="Portuguese" ? $msgs["$msgid"][0] : $msgs["$msgid"][1] );
    $langmsg = _registrobr_set_encode($langmsg);
    return $langmsg;
}

?>
