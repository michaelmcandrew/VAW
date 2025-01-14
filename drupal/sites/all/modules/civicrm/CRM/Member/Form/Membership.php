<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */

require_once 'CRM/Member/Form.php';
require_once 'CRM/Member/PseudoConstant.php';
require_once "CRM/Custom/Form/CustomData.php";
require_once "CRM/Core/BAO/CustomGroup.php";

/**
 * This class generates form components for Membership Type
 * 
 */
class CRM_Member_Form_Membership extends CRM_Member_Form
{
    protected $_memType = null;
    
    protected $_onlinePendingContributionId;

    public $_mode;
    
    protected $_recurMembershipTypes;

    protected $_memTypeSelected;

    public function preProcess()  
    {  
        //custom data related code
        $this->_cdType     = CRM_Utils_Array::value( 'type', $_GET );
        $this->assign('cdType', false);
        if ( $this->_cdType ) {
            $this->assign('cdType', true);
            return CRM_Custom_Form_CustomData::preProcess( $this );
        }
        
        // get price set id.
        $this->_priceSetId  = CRM_Utils_Array::value( 'priceSetId', $_GET );
        $this->set( 'priceSetId',  $this->_priceSetId );
        $this->assign( 'priceSetId', $this->_priceSetId );

        // action
        $this->_action    = CRM_Utils_Request::retrieve( 'action', 'String',
                                                         $this, false, 'add' );
        $this->_id        = CRM_Utils_Request::retrieve( 'id', 'Positive',
                                                         $this );
        $this->_contactID = CRM_Utils_Request::retrieve( 'cid', 'Positive',
                                                         $this );
        $this->_processors = array( );
        
        
        // check for edit permission
        if ( !CRM_Core_Permission::checkActionPermission( 'CiviMember', $this->_action ) ) {
            CRM_Core_Error::fatal( ts( 'You do not have permission to access this page' ) );
        }
        
        $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this );
        $this->assign('context', $this->_context );
        
        if ( $this->_id ) {
            $this->_memType = CRM_Core_DAO::getFieldValue( "CRM_Member_DAO_Membership", $this->_id, 
                                                           "membership_type_id");
        } 
        $this->_mode = CRM_Utils_Request::retrieve( 'mode', 'String', $this );
        $this->assign( 'membershipMode', $this->_mode );
        
        if ( $this->_mode ) {
            $this->_paymentProcessor = array( 'billing_mode' => 1 );
            $validProcessors         = array( );
            $processors = CRM_Core_PseudoConstant::paymentProcessor( false, false, "billing_mode IN ( 1, 3 )" );
            
            foreach ( $processors as $ppID => $label ) {
                require_once 'CRM/Core/BAO/PaymentProcessor.php';
                require_once 'CRM/Core/Payment.php';
                $paymentProcessor =& CRM_Core_BAO_PaymentProcessor::getPayment( $ppID, $this->_mode );
                if ( $paymentProcessor['payment_processor_type'] == 'PayPal' && !$paymentProcessor['user_name'] ) {
                    continue;
                } else if ( $paymentProcessor['payment_processor_type'] == 'Dummy' && $this->_mode == 'live' ) {
                    continue;
                } else {
                    $paymentObject =& CRM_Core_Payment::singleton( $this->_mode, $paymentProcessor, $this );
                    $error = $paymentObject->checkConfig( );
                    if ( empty( $error ) ) {
                        $validProcessors[$ppID] = $label;
                    }
                    $paymentObject = null;
                }
            }
            if ( empty( $validProcessors )  ) {
               CRM_Core_Error::fatal( ts( 'Could not find valid payment processor for this page' ) );
            } else {
                $this->_processors = $validProcessors;  
            }
            // also check for billing information
            // get the billing location type
            $locationTypes =& CRM_Core_PseudoConstant::locationType( );
            // CRM-8108 remove ts around Billing location type
            //$this->_bltID = array_search( ts('Billing'),  $locationTypes );
            $this->_bltID = array_search( 'Billing',  $locationTypes );
            if ( ! $this->_bltID ) {
                CRM_Core_Error::fatal( ts( 'Please set a location type of %1', array( 1 => 'Billing' ) ) );
            }
            $this->set   ( 'bltID', $this->_bltID );
            $this->assign( 'bltID', $this->_bltID );
                       
            $this->_fields = array( );
            
            require_once 'CRM/Core/Payment/Form.php';
            CRM_Core_Payment_Form::setCreditCardFields( $this );
            
            // this required to show billing block    
            $this->assign_by_ref( 'paymentProcessor', $paymentProcessor );
            $this->assign( 'hidePayPalExpress', true );
        }
        
        if ( $this->_action & CRM_Core_Action::ADD ) {
            require_once 'CRM/Member/BAO/Membership.php';
            //check whether any active membership statuses are available - redirects back to contact summary if not
            CRM_Member_BAO_Membership::statusAvilability($this->_contactID);

            if ( $this->_contactID ) {
                //check whether contact has a current membership so we can alert user that they may want to do a renewal instead
                $hasMembership = CRM_Member_BAO_Membership::getContactMembership( $this->_contactID, null, 0 );
                if ( !empty( $hasMembership ) ) {
                    $hasMembership['membership_type'] = CRM_Core_DAO::getFieldValue( 'CRM_Member_DAO_MembershipType', 
                                                                                    $hasMembership['membership_type_id'],
                                                                                    'name', 'id' );
                    $hasMembership['membership_status'] = CRM_Core_DAO::getFieldValue( 'CRM_Member_DAO_MembershipStatus', 
                                                                                    $hasMembership['status_id'],
                                                                                    'label', 'id' );
                    $membershipTab = CRM_Utils_System::url( 'civicrm/contact/view',
                                                        "reset=1&force=1&cid={$this->_contactID}&selectedChild=member" );
                    if ( $this->_mode ) {
                        $renewUrl = CRM_Utils_System::url( 'civicrm/contact/view/membership',
                                                        "reset=1&action=renew&cid={$this->_contactID}&id={$hasMembership['id']}&context=membership&selectedChild=member&mode=live" );
                    } else {
                        $renewUrl = CRM_Utils_System::url( 'civicrm/contact/view/membership',
                                                        "reset=1&action=renew&cid={$this->_contactID}&id={$hasMembership['id']}&context=membership&selectedChild=member" );
                    }
                    if ( $hasMembership['membership_end_date'] ) {
                        CRM_Core_Session::setStatus( ts('This contact has an existing %1 membership record with %2 status and end date of %3. <a href="%4">Click here if you want to renew this membership</a> (rather than creating a new membership record). <a href="%5">Click here to view all existing and / or expired memberships for this contact.</a>', array( 1 => $hasMembership['membership_type'], 2 => $hasMembership['membership_status'], 3 => CRM_Utils_date::customformat($hasMembership['membership_end_date']), 4 => $renewUrl, 5 => $membershipTab ) ) );                        
                    } else {
                        CRM_Core_Session::setStatus( ts('This contact has an existing %1 membership record with %2 status. <a href="%3">Click here if you want to renew this membership</a> (rather than creating a new membership record). <a href="%4">Click here to view all existing and / or expired memberships for this contact.</a>', array( 1 => $hasMembership['membership_type'], 2 => $hasMembership['membership_status'], 3 => $renewUrl, 4 => $membershipTab ) ) );                        
                    }
                }
            }
        }
        
        // when custom data is included in this page
        if ( CRM_Utils_Array::value( "hidden_custom", $_POST ) ) {
            CRM_Custom_Form_Customdata::preProcess( $this );
            CRM_Custom_Form_Customdata::buildQuickForm( $this );
            CRM_Custom_Form_Customdata::setDefaultValues( $this );
        }
        
        // CRM-4395, get the online pending contribution id.
        $this->_onlinePendingContributionId = null;
        if ( !$this->_mode && $this->_id && ($this->_action & CRM_Core_Action::UPDATE) ) {
            require_once 'CRM/Contribute/BAO/Contribution.php';
            $this->_onlinePendingContributionId = CRM_Contribute_BAO_Contribution::checkOnlinePendingContribution( $this->_id, 
                                                                                                                   'Membership' );
        }
        $this->assign( 'onlinePendingContributionId', $this->_onlinePendingContributionId );

        require_once "CRM/Core/BAO/Email.php";
        $this->_fromEmails = CRM_Core_BAO_Email::getFromEmail( );
        
        parent::preProcess( );
    }
    
    /**
     * This function sets the default values for the form. MobileProvider that in edit/view mode
     * the default values are retrieved from the database
     * 
     * @access public
     * @return None
     */
    public function setDefaultValues( ) 
    {
        if ( $this->_cdType ) {
            return CRM_Custom_Form_CustomData::setDefaultValues( $this );
        }
        
        $defaults = array( );
        if ( $this->_priceSetId ) {
            return CRM_Price_BAO_Set::setDefaultPriceSet($this, $defaults);
        }

        $defaults =& parent::setDefaultValues( );
        
        //setting default join date and receive date
        list( $now ) = CRM_Utils_Date::setDateDefaults( );
        if ($this->_action == CRM_Core_Action::ADD) {
            $defaults['receive_date'] = $now;
        }
        
        if ( is_numeric( $this->_memType ) ) {
            $defaults["membership_type_id"] = array();
            $defaults["membership_type_id"][0] =  
                CRM_Core_DAO::getFieldValue( 'CRM_Member_DAO_MembershipType', 
                                             $this->_memType, 
                                             'member_of_contact_id', 
                                             'id' );
            $defaults["membership_type_id"][1] = $this->_memType;
        } else {
            $defaults["membership_type_id"]    =  $this->_memType;
        }
        
        if ( CRM_Utils_Array::value( 'id' , $defaults ) ) {
            if ( $this->_onlinePendingContributionId ) {
                $defaults['record_contribution'] = $this->_onlinePendingContributionId;
            } else {
                $contributionId = CRM_Core_DAO::singleValueQuery( "
  SELECT contribution_id 
  FROM civicrm_membership_payment 
  WHERE membership_id = $this->_id 
  ORDER BY contribution_id 
  DESC limit 1" );
               
                if ( $contributionId ) {
                    $defaults['record_contribution'] = $contributionId;
                }
            }
        }
                
        if ( CRM_Utils_Array::value( 'record_contribution', $defaults ) && ! $this->_mode ) {
            $contributionParams   = array( 'id' => $defaults['record_contribution'] );
            $contributionIds      = array( );
            
            //keep main object campaign in hand.
            $memberCampaignId = CRM_Utils_Array::value( 'campaign_id', $defaults );
            
            require_once "CRM/Contribute/BAO/Contribution.php";
            CRM_Contribute_BAO_Contribution::getValues( $contributionParams, $defaults, $contributionIds );
            
            //get back original object campaign id.
            $defaults['campaign_id'] = $memberCampaignId;
            
            list( $defaults['receive_date'] ) = CRM_Utils_Date::setDateDefaults( $defaults['receive_date'] );
            
            // Contribution::getValues() over-writes the membership record's source field value - so we need to restore it.
            if ( CRM_Utils_Array::value( 'membership_source', $defaults ) ) {
                $defaults['source'] = $defaults['membership_source'];
            }
        }
        
        // User must explicitly choose to send a receipt in both add and update mode.
        $defaults['send_receipt'] = 0; 
        
        if ( $this->_action & CRM_Core_Action::UPDATE ) {
            // in this mode by default uncheck this checkbox
            unset($defaults['record_contribution']);
        }
        
        if ( CRM_Utils_Array::value( 'id' , $defaults ) ) {
            $subscriptionCancelled = CRM_Member_BAO_Membership::isSubscriptionCancelled( $this->_id );
        }
        
        $alreadyAutoRenew = false;
        if ( CRM_Utils_Array::value( 'contribution_recur_id', $defaults ) && !$subscriptionCancelled ) {
            $defaults['auto_renew'] = 1;
            $alreadyAutoRenew = true;
        }
        $this->assign( 'alreadyAutoRenew', $alreadyAutoRenew );
        
        $this->assign( "member_is_test", CRM_Utils_Array::value('member_is_test',$defaults) );
        
        $this->assign( 'membership_status_id', CRM_Utils_Array::value('status_id',$defaults) );
        
        if ( CRM_Utils_Array::value( 'is_pay_later', $defaults) ) {
            $this->assign( 'is_pay_later', true ); 
        }
        if ( $this->_mode ) {
            $fields = array( );
            
            foreach ( $this->_fields as $name => $dontCare ) {
                $fields[$name] = 1;
            }
            $names = array( "first_name", "middle_name", "last_name","street_address-{$this->_bltID}",
                            "city-{$this->_bltID}", "postal_code-{$this->_bltID}","country_id-{$this->_bltID}",
                            "state_province_id-{$this->_bltID}"
                            );
            foreach ($names as $name) {
                $fields[$name] = 1;
            }
            $fields["state_province-{$this->_bltID}"] = 1;
            $fields["country-{$this->_bltID}"       ] = 1;
            $fields["email-{$this->_bltID}"         ] = 1;
            $fields["email-Primary"                 ] = 1;

            if ( $this->_contactID ) {
                require_once "CRM/Core/BAO/UFGroup.php";
                CRM_Core_BAO_UFGroup::setProfileDefaults( $this->_contactID, $fields, $this->_defaults );
            }

            // use primary email address if billing email address is empty
            if ( empty( $this->_defaults["email-{$this->_bltID}"] ) &&
                 ! empty( $this->_defaults["email-Primary"] ) ) {
                $defaults["email-{$this->_bltID}"] = $this->_defaults["email-Primary"];
            }

            foreach ($names as $name) {
                if ( ! empty( $this->_defaults[$name] ) ) {
                    $defaults["billing_" . $name] = $this->_defaults[$name];
                }
            }

//             // hack to simplify credit card entry for testing
//             $defaults['credit_card_type']     = 'Visa';
//             $defaults['credit_card_number']   = '4807731747657838';
//             $defaults['cvv2']                 = '000';
//             $defaults['credit_card_exp_date'] = array( 'Y' => '2012', 'M' => '05' );
            
        }
                
        $dates = array( 'join_date', 'start_date', 'end_date' );
        foreach( $dates as $key ) {
            if ( CRM_Utils_Array::value( $key, $defaults ) ) {
                list( $defaults[$key] ) = CRM_Utils_Date::setDateDefaults( CRM_Utils_Array::value( $key, $defaults ) );
            }
        }
        
        //setting default join date if there is no join date
        if ( !CRM_Utils_Array::value('join_date', $defaults ) ) {
            $defaults['join_date']    = $now;
        }
        
        if ( CRM_Utils_Array::value( 'membership_end_date', $defaults) ) {
            $this->assign( 'endDate', $defaults['membership_end_date'] );
        }
        
        return $defaults;
        
    }
    
    /**
     * Function to build the form
     *
     * @return None
     * @access public
     */
    public function buildQuickForm( ) 
    {
        if ( $this->_cdType ) {
            return CRM_Custom_Form_CustomData::buildQuickForm( $this );
        }
        
        // build price set form.
        $buildPriceSet = false;
        if ( $this->_priceSetId || CRM_Utils_Array::value( 'price_set_id', $_POST ) ) {
            if ( CRM_Utils_Array::value( 'price_set_id', $_POST ) ) {
                $buildPriceSet = true;
            }
            $getOnlyPriceSetElements = true;
            if ( !$this->_priceSetId ) { 
                $this->_priceSetId = $_POST['price_set_id'];
                $getOnlyPriceSetElements = false;
            }
            
            $this->set( 'priceSetId', $this->_priceSetId );
            require_once 'CRM/Price/BAO/Set.php';
            CRM_Price_BAO_Set::buildPriceSet( $this );

            $optionsMembershipTypes = array( );
            foreach( $this->_priceSet['fields'] as $pField ) {
                if ( empty($pField['options']) ) {
                    continue;                    
                }
                foreach( $pField['options'] as $opId => $opValues ) {
                    $optionsMembershipTypes[$opId] = CRM_Utils_Array::value('membership_type_id', $opValues, 0);
                }
            }
            
            $this->assign( 'autoRenewOption', CRM_Price_BAO_Set::checkAutoRenewForPriceSet( $this->_priceSetId ) );

            $this->assign( 'optionsMembershipTypes', $optionsMembershipTypes);
            $this->assign( 'contributionType', CRM_Utils_Array::value('contribution_type_id', $this->_priceSet) );

            // get only price set form elements.
            if ( $getOnlyPriceSetElements ) return;
        }

        // use to build form during form rule.
        $this->assign( 'buildPriceSet', $buildPriceSet );

        if ( $this->_action & CRM_Core_Action::ADD ) {
            $buildPriceSet = false;
            require_once 'CRM/Price/BAO/Set.php';
            $priceSets = CRM_Price_BAO_Set::getAssoc( false, 'CiviMember' );
            if ( !empty( $priceSets ) ) {
                $buildPriceSet = true;
            }

            if ( $buildPriceSet ) {
                $this->add( 'select', 'price_set_id', ts( 'Choose price set' ),
                            array( '' => ts( 'Choose price set' )) + $priceSets,
                            null, array('onchange' => "buildAmount( this.value );" ) );
            }
            $this->assign( 'hasPriceSets', $buildPriceSet );
        }

        //need to assign custom data type and subtype to the template
        $this->assign('customDataType', 'Membership');
        $this->assign('customDataSubType',  $this->_memType );
        $this->assign('entityID',  $this->_id );
        
        if ( $this->_action & CRM_Core_Action::DELETE ) {
            $this->addButtons(array( 
                                    array ( 'type'      => 'next', 
                                            'name'      => ts('Delete'), 
                                            'spacing'   => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', 
                                            'isDefault' => true   ), 
                                    array ( 'type'      => 'cancel', 
                                            'name'      => ts('Cancel') ), 
                                    ) 
                              );
            return;
        }
        
        if ( $this->_context == 'standalone' ) {
            require_once 'CRM/Contact/Form/NewContact.php';
            CRM_Contact_Form_NewContact::buildQuickForm( $this );
        }        
        
        $selOrgMemType[0][0] = $selMemTypeOrg[0] = ts('- select -');
        
        $dao = new CRM_Member_DAO_MembershipType();
        $dao->domain_id = CRM_Core_Config::domainID( );
        $dao->find();
        $membershipType = array( );
        while ($dao->fetch()) {
            if ($dao->is_active) {
                $membershipType[$dao->id] = $dao->name;
                if ( $this->_mode && ! $dao->minimum_fee ) {
                    continue;
                } else {
                    if ( !CRM_Utils_Array::value($dao->member_of_contact_id,$selMemTypeOrg) ) {
                        $selMemTypeOrg[$dao->member_of_contact_id] = 
                            CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_Contact', 
                                                         $dao->member_of_contact_id, 
                                                         'display_name', 
                                                         'id' );
                   
                        $selOrgMemType[$dao->member_of_contact_id][0] = ts('- select -');
                    }                
                    if ( !CRM_Utils_Array::value($dao->id,$selOrgMemType[$dao->member_of_contact_id]) ) {
                        $selOrgMemType[$dao->member_of_contact_id][$dao->id] = $dao->name;
                    }
                }
            }
        }

        // show organization by default, if only one organization in
        // the list 
        if ( count($selMemTypeOrg) == 2 ) {
            unset($selMemTypeOrg[0], $selOrgMemType[0][0]);
        }
        //sort membership organization and type, CRM-6099
        natcasesort( $selMemTypeOrg );
        foreach( $selOrgMemType as $index => $orgMembershipType ) {
            natcasesort( $orgMembershipType );
            $selOrgMemType[$index] = $orgMembershipType;
        }
        
        $memTypeJs = array( 'onChange' => "buildCustomData( 'Membership', this.value );");
        
        //build the form for auto renew.
        $recurProcessor = $autoRenew = array( );
        if ( $this->_mode || ( $this->_action & CRM_Core_Action::UPDATE ) ) {
            $autoRenewElement = $this->addElement( 'checkbox', 
                                                   'auto_renew', 
                                                   ts('Membership renewed automatically'),
                                                   null, 
                                                   array( 'onclick' => "buildReceiptANDNotice( );" ) );
            
            if ( $this->_mode ) {
                //get the valid recurring processors.
                $recurring = CRM_Core_PseudoConstant::paymentProcessor( false, false, 'is_recur = 1' );
                $recurProcessor = array_intersect_assoc( $this->_processors, $recurring );
                $autoRenew = array( );
                if ( !empty( $recurProcessor ) ) {
                    if ( !empty( $membershipType ) ) {
                        $sql = '
SELECT  id, 
        auto_renew,
        duration_unit,
        duration_interval
 FROM   civicrm_membership_type
WHERE   id IN ( '. implode( ' , ', array_keys( $membershipType ) ) .' )';  
                        $recurMembershipTypes = CRM_Core_DAO::executeQuery( $sql );
                        while ( $recurMembershipTypes->fetch( ) ) {
                            $autoRenew[$recurMembershipTypes->id] = $recurMembershipTypes->auto_renew;
                            foreach ( array( 'id', 'auto_renew', 'duration_unit', 'duration_interval' ) as $fld ) {  
                                $this->_recurMembershipTypes[$recurMembershipTypes->id][$fld] = $recurMembershipTypes->$fld;
                            }
                        }
                    }
                    $memTypeJs = array( 'onChange' => 
                                        "buildCustomData( 'Membership', this.value ); buildAutoRenew(this.value, null );");
                }
                
            }
        }
        $allowAutoRenew = false;
        if ( $this->_mode && !empty( $recurProcessor ) ) $allowAutoRenew = true;  
        $this->assign( 'allowAutoRenew',   $allowAutoRenew );
        $this->assign( 'autoRenewOptions', json_encode($autoRenew) );
        $this->assign( 'recurProcessor',   json_encode( $recurProcessor ) );
        
        $sel =& $this->addElement('hierselect', 
                                  'membership_type_id', 
                                  ts('Membership Organization and Type'), 
                                  $memTypeJs );
        
        $sel->setOptions(array($selMemTypeOrg,  $selOrgMemType));
        $elements = array( );
        if ( $sel ) {
            $elements[] = $sel;
        }
                
        $this->applyFilter('__ALL__', 'trim');
        
        $this->addDate( 'join_date', ts('Member Since'), false, array( 'formatType' => 'activityDate') );
        $this->addDate( 'start_date', ts('Start Date'), false, array( 'formatType' => 'activityDate') );
        $endDate = $this->addDate( 'end_date', ts('End Date'), false, array( 'formatType' => 'activityDate') );
        if ( $endDate ) {
            $elements[] = $endDate;
        }
        
        $this->add('text', 'source', ts('Source'), 
                   CRM_Core_DAO::getAttribute( 'CRM_Member_DAO_Membership', 'source' ) );
        
        //CRM-7362 --add campaigns.
        require_once 'CRM/Campaign/BAO/Campaign.php';
        $campaignId = null;
        if ( $this->_id ) {
            $campaignId = CRM_Core_DAO::getFieldValue( 'CRM_Member_DAO_Membership', $this->_id, 'campaign_id' ); 
        }
        CRM_Campaign_BAO_Campaign::addCampaign( $this, $campaignId );
        
        if ( !$this->_mode ) {
            $this->add('select', 'status_id', ts( 'Membership Status' ), 
                       array('' =>ts('- select -')) + CRM_Member_PseudoConstant::membershipStatus(null, null, 'label'));
            $statusOverride = $this->addElement('checkbox', 'is_override', 
                                                ts('Status Override?'), null, 
                                                array( 'onClick' => 'showHideMemberStatus()'));
            if ( $statusOverride ) {
                $elements[] = $statusOverride;
            }
                        
            $this->addElement('checkbox', 'record_contribution', ts('Record Membership Payment?') );
            
            require_once 'CRM/Contribute/PseudoConstant.php';
            $this->add('select', 'contribution_type_id', 
                       ts( 'Contribution Type' ), 
                       array(''=>ts( '- select -' )) + CRM_Contribute_PseudoConstant::contributionType( ) );
            
            $this->add('text', 'total_amount', ts('Amount'));
            $this->addRule('total_amount', ts('Please enter a valid amount.'), 'money');

            $this->addDate( 'receive_date', ts('Received'), false, array( 'formatType' => 'activityDate') );

            $this->add('select', 'payment_instrument_id', 
                       ts( 'Paid By' ), 
                       array(''=>ts( '- select -' )) + CRM_Contribute_PseudoConstant::paymentInstrument( ),
                       false, array( 'onChange' => "return showHideByValue('payment_instrument_id','4','checkNumber','table-row','select',false);"));
            $this->add('text', 'trxn_id', ts('Transaction ID'));
            $this->addRule( 'trxn_id', ts('Transaction ID already exists in Database.'),
                            'objectExists', array( 'CRM_Contribute_DAO_Contribution', $this->_id, 'trxn_id' ) );
            
            $allowStatuses = array( );
            $statuses = CRM_Contribute_PseudoConstant::contributionStatus( );
            if ( $this->_onlinePendingContributionId ) {
                $statusNames = CRM_Contribute_PseudoConstant::contributionStatus( null, 'name' );
                foreach ( $statusNames as $val => $name ) {
                    if ( in_array($name, array('In Progress', 'Overdue')) ) continue;
                    $allowStatuses[$val] = $statuses[$val]; 
                }
            } else {
                $allowStatuses = $statuses;
            }
            $this->add('select', 'contribution_status_id',
                       ts('Payment Status'), $allowStatuses );
            $this->add( 'text', 'check_number', ts('Check Number'), 
                        CRM_Core_DAO::getAttribute( 'CRM_Contribute_DAO_Contribution', 'check_number' ) );
        }else{
                //add field for amount to allow an amount to be entered that differs from minimum
                $this->add('text', 'total_amount', ts('Amount'));
         }
        $this->addElement( 'checkbox', 
                           'send_receipt', 
                           ts('Send Confirmation and Receipt?'), null, 
                           array( 'onclick' => "showHideByValue( 'send_receipt', '', 'notice', 'table-row', 'radio', false); showHideByValue( 'send_receipt', '', 'fromEmail', 'table-row', 'radio', false);" ) );

        $this->add( 'select', 'from_email_address', ts('Receipt From'), $this->_fromEmails );

        $this->add('textarea', 'receipt_text_signup', ts('Receipt Message') );
        if ( $this->_mode ) {
            
            $this->add( 'select', 'payment_processor_id',
                        ts( 'Payment Processor' ),
                        $this->_processors, true, 
                        array( 'onChange' => "buildAutoRenew( null, this.value );") );
            require_once 'CRM/Core/Payment/Form.php';
            CRM_Core_Payment_Form::buildCreditCard( $this, true );
        }
        
        // Retrieve the name and email of the contact - this will be the TO for receipt email
        if ( $this->_contactID ) {
            require_once 'CRM/Contact/BAO/Contact/Location.php';
            list( $this->_memberDisplayName, 
                  $this->_memberEmail ) = CRM_Contact_BAO_Contact_Location::getEmailDetails( $this->_contactID );
        
            $this->assign( 'emailExists', $this->_memberEmail );
            $this->assign( 'displayName', $this->_memberDisplayName );
            
        }
        
        $isRecur = false;
        if ( $this->_action & CRM_Core_Action::UPDATE ) {
            $recurContributionId = CRM_Core_DAO::getFieldValue( 'CRM_Member_DAO_Membership', $this->_id,
                                                                'contribution_recur_id' );
            if ( $recurContributionId ) {
                $isRecur = true;
                require_once 'CRM/Member/BAO/Membership.php'; 
                if ( CRM_Member_BAO_Membership::isCancelSubscriptionSupported( $this->_id ) ) {
                    $this->assign( 'cancelAutoRenew', 
                                   CRM_Utils_System::url( 'civicrm/contribute/unsubscribe', "reset=1&mid={$this->_id}" ) );
                }
                foreach ( $elements as $elem ) {
                    $elem->freeze( );
                }
            }
        }
        $this->assign( 'isRecur', $isRecur );
        
        $this->addFormRule(array('CRM_Member_Form_Membership', 'formRule'), $this );
        
        require_once "CRM/Core/BAO/Preferences.php";
        $mailingInfo =& CRM_Core_BAO_Preferences::mailingPreferences();
        $this->assign( 'outBound_option', $mailingInfo['outBound_option'] );
        
        parent::buildQuickForm( );
    }
    
    /**
     * Function for validation
     *
     * @param array $params (ref.) an assoc array of name/value pairs
     *
     * @return mixed true or array of errors
     * @access public
     * @static
     */
    public function formRule( $params, $files, $self ) 
    {
        $errors = array( );
        
        $priceSetId = CRM_Utils_Array::value( 'price_set_id', $params );
        
        if ( $priceSetId ) {
            require_once 'CRM/Price/BAO/Field.php';
            CRM_Price_BAO_Field::priceSetValidation( $priceSetId, $params, $errors );
            
            $priceFieldIDS = array();
            foreach ($self->_priceSet['fields'] as $priceIds => $dontCare ) {
                
                if (!empty($params['price_'.$priceIds])){
                    if (is_array($params['price_'.$priceIds])) {
                        foreach( $params['price_'.$priceIds] as $priceFldVal => $isSet ) {
                            if ($isSet) {
                                $priceFieldIDS[] = $priceFldVal;
                            }
                        }
                    } else {
                        $priceFieldIDS[] = $params['price_'.$priceIds];
                    }
                }
            }
            
            if ( !empty($priceFieldIDS) ) {
                $ids = implode (',', $priceFieldIDS);
                
                $count = CRM_Price_BAO_Set::getMembershipCount($ids);
                foreach( $count as $id => $occurance ) {
                    if ($occurance > 1) {
                        $errors['_qf_default'] = ts( 'Select at most one option associated with the same membership type.' );
                    }
                }
                
                foreach ($priceFieldIDS as $priceFieldId) {
                    if( $id = CRM_Core_DAO::getFieldValue( 'CRM_Price_DAO_FieldValue', $priceFieldId, 'membership_type_id' )) {
                        $self->_memTypeSelected[$id] = $id;
                    } 
                }
            }
        } else if ( !CRM_Utils_Array::value( 1, $params['membership_type_id'] ) ) {
            $errors['membership_type_id'] = ts('Please select a membership type.');
        } else {
            $self->_memTypeSelected[] = $params['membership_type_id'][1];                       
        }

        // Return error if empty $self->_memTypeSelected
        if ( $priceSetId && empty($errors) && empty($self->_memTypeSelected) ) {
            $errors['_qf_default'] = ts( 'Select at least one membership option.' );  
        }

        if ( !empty($errors) && (count($self->_memTypeSelected) > 1) ) {
            require_once 'CRM/Member/BAO/MembershipType.php';
            $memberOfContacts = CRM_Member_BAO_MembershipType::getMemberOfContactByMemTypes( $self->_memTypeSelected );
            $duplicateMemberOfContacts = array_count_values($memberOfContacts);
            foreach( $duplicateMemberOfContacts as $countDuplicate ) {
                if ($countDuplicate > 1) {
                    $errors['_qf_default'] = ts( 'Please do not select more than one membership associated with the same organization.' ); 
                }
            }
        }

        //check if contact is selected in standalone mode
        if ( isset( $params['contact_select_id'][1] ) && !$params['contact_select_id'][1] ) {
            $errors['contact[1]'] = ts('Please select a contact or create new contact');
        }

        if ( !empty($errors) ) {
            return $errors;
        }

        if ( $priceSetId && !$self->_mode && !CRM_Utils_Array::value('record_contribution', $params) ) {
            $errors['record_contribution'] = ts('Record Membership Payment is required when you using price set.'); 
        }
        
        if ( CRM_Utils_Array::value( 'payment_processor_id', $params ) ) {
            // make sure that credit card number and cvv are valid
            require_once 'CRM/Utils/Rule.php';
            if ( CRM_Utils_Array::value( 'credit_card_type', $params ) ) {
                if ( CRM_Utils_Array::value( 'credit_card_number', $params ) &&
                     ! CRM_Utils_Rule::creditCardNumber( $params['credit_card_number'], $params['credit_card_type'] ) ) {
                    $errors['credit_card_number'] = ts( "Please enter a valid Credit Card Number" );
                }
                
                if ( CRM_Utils_Array::value( 'cvv2', $params ) &&
                     ! CRM_Utils_Rule::cvv( $params['cvv2'], $params['credit_card_type'] ) ) {
                    $errors['cvv2'] =  ts( "Please enter a valid Credit Card Verification Number" );
                }
            }
        }
        
        $joinDate = null;
        if ( CRM_Utils_Array::value( 'join_date', $params ) ) {
            require_once 'CRM/Member/BAO/MembershipType.php';
            
            $joinDate = CRM_Utils_Date::processDate( $params['join_date'] );
                        
            foreach( $self->_memTypeSelected as $memType ) {
                $startDate = null;
                if ( CRM_Utils_Array::value( 'start_date', $params ) ) {
                    $startDate = CRM_Utils_Date::processDate( $params['start_date'] );
                }
                
                // if end date is set, ensure that start date is also set
                // and that end date is later than start date
                // If selected membership type has duration unit as 'lifetime'
                // and end date is set, then give error
                $endDate = null;
                if ( CRM_Utils_Array::value( 'end_date', $params ) ) {
                    $endDate = CRM_Utils_Date::processDate( $params['end_date'] );
                }
                
                $membershipDetails = CRM_Member_BAO_MembershipType::getMembershipTypeDetails( $memType );
                
                if ( $startDate && CRM_Utils_Array::value( 'period_type', $membershipDetails ) == 'rolling' ) {
                    if ( $startDate < $joinDate ) {
                        $errors['start_date'] = ts( 'Start date must be the same or later than Member since.' );
                    }
                }
                
                if ( $endDate ) {
                    if ( $membershipDetails['duration_unit'] == 'lifetime' ) {
                        $errors['end_date'] = ts('The selected Membership Type has a lifetime duration. You cannot specify an End Date for lifetime memberships. Please clear the End Date OR select a different Membership Type.');
                    } else {
                        if ( ! $startDate ) {
                            $errors['start_date'] = ts( 'Start date must be set if end date is set.' );
                        }
                        if ( $endDate < $startDate ) {
                            $errors['end_date'] = ts('End date must be the same or later than start date.' );
                        }
                    }
                }
                
                //  Default values for start and end dates if not supplied
                //  on the form
                $defaultDates = 
                    CRM_Member_BAO_MembershipType::getDatesForMembershipType( $memType,
                                                                              $joinDate,
                                                                              $startDate,
                                                                              $endDate );
                
                if ( !$startDate ) {
                    $startDate = CRM_Utils_Array::value( 'start_date',
                                                         $defaultDates );
                }
                if ( !$endDate ) {
                    $endDate = CRM_Utils_Array::value( 'end_date',
                                                       $defaultDates );
                }

                //CRM-3724, check for availability of valid membership status.
                if ( !CRM_Utils_Array::value( 'is_override',  $params ) && !isset($errors['_qf_default']) ) {
                    require_once 'CRM/Member/BAO/MembershipStatus.php';
                    $calcStatus = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate( $startDate, 
                                                                                              $endDate, 
                                                                                              $joinDate, 
                                                                                              'today', 
                                                                                              true );
                    if ( empty( $calcStatus ) ) {
                        $url = CRM_Utils_System::url( 'civicrm/admin/member/membershipStatus', 'reset=1&action=browse' );
                        $errors['_qf_default'] = ts( 'There is no valid Membership Status available for selected membership dates.' );
                        $status = ts( 'Oops, it looks like there is no valid membership status available for the given membership dates. You can <a href="%1">Configure Membership Status Rules</a>.',  array( 1 => $url ) );
                        if ( !$self->_mode ) { 
                            $status .= ' ' . ts( 'OR You can sign up by setting Status Override? to true.' );
                        }
                        CRM_Core_Session::setStatus( $status );
                    }
                }
            }
        } else {
            $errors['join_date'] = ts('Please enter the Member Since.');
        }

        if ( isset( $params['is_override'] ) &&
             $params['is_override']          &&
             ! CRM_Utils_Array::value( 'status_id', $params ) ) {
            $errors['status_id'] = ts('Please enter the status.');
        }
        
        //total amount condition arise when membership type having no
        //minimum fee
        if ( isset( $params['record_contribution'] ) ) { 
            if ( ! $params['contribution_type_id'] ) {
                $errors['contribution_type_id'] = ts('Please enter the contribution Type.');
            } 
            if ( !$params['total_amount'] ) {
                $errors['total_amount'] = ts('Please enter the contribution.'); 
            }
        }
        
        // validate contribution status for 'Failed'.
        if ( $self->_onlinePendingContributionId && 
             CRM_Utils_Array::value( 'record_contribution', $params ) && 
             (CRM_Utils_Array::value( 'contribution_status_id', $params ) == 
              array_search( 'Failed', CRM_Contribute_PseudoConstant::contributionStatus(null, 'name'))) ) {
            $errors['contribution_status_id'] = ts( "Please select a valid payment status before updating." );
        }
        
        return empty($errors) ? true : $errors;
    }
       
    /**
     * Function to process the form
     *
     * @access public
     * @return None
     */
    public function postProcess() 
    {
        require_once 'CRM/Member/BAO/Membership.php';
        require_once 'CRM/Member/BAO/MembershipType.php';
        require_once 'CRM/Member/BAO/MembershipStatus.php';

        if ( $this->_action & CRM_Core_Action::DELETE ) {
            CRM_Member_BAO_Membership::deleteRelatedMemberships( $this->_id );
            CRM_Member_BAO_Membership::deleteMembership( $this->_id );
            return;
        }
        $config = CRM_Core_Config::singleton(); 
        // get the submitted form values.  
        $this->_params = $formValues = $this->controller->exportValues( $this->_name );
                                
        $params = $ids = array( );
        
        $membershipTypeValues = array();
        foreach ( $this->_memTypeSelected as $memType ) {
            $membershipTypeValues[$memType]['membership_type_id'] = $memType;
        }

        //take the required membership recur values.
        if ( $this->_mode && 
             CRM_Utils_Array::value( 'auto_renew', $this->_params ) ) {
            $params['is_recur'] = $this->_params['is_recur'] = $formValues['is_recur'] = true;
            $mapping = array( 'frequency_interval'  => 'duration_interval',
                              'frequency_unit' => 'duration_unit'  );
            
            $count = 0;
            foreach( $this->_memTypeSelected as $memType ) {
                $recurMembershipTypeValues = CRM_Utils_Array::value( $memType, 
                                                                     $this->_recurMembershipTypes, array( ) ); 
                foreach ( $mapping as $mapVal => $mapParam ) {
                    $membershipTypeValues[$memType][$mapVal] =
                        CRM_Utils_Array::value( $mapParam, 
                                                $recurMembershipTypeValues );
                     if ( !$count ) {
                         $this->_params[$mapVal] = $formValues[$mapVal] =
                             CRM_Utils_Array::value( $mapParam, 
                                                     $recurMembershipTypeValues );
                     }
                }
                $count++;
            }
            
            // unset send-receipt option, since receipt will be sent when ipn is received.
            unset( $this->_params['send_receipt'], $formValues['send_receipt'] );
        }
        
        // process price set and get total amount and line items.
        $lineItem = array( );
        $priceSetId = null;
        if ( $priceSetId = CRM_Utils_Array::value( 'price_set_id', $this->_params ) ) {
            require_once 'CRM/Price/BAO/Set.php';
            CRM_Price_BAO_Set::processAmount( $this->_priceSet['fields'], 
                                              $this->_params, $lineItem[$priceSetId] );
            $params['total_amount'] = CRM_Utils_Array::value( 'amount', $this->_params );
        }
        if ( !CRM_Utils_Array::value( 'total_amount', $this->_params ) ) {
            $params['total_amount'] = $this->_values['total_amount']; 
        }        
        
        // set the contact, when contact is selected
        require_once 'CRM/Contact/BAO/Contact/Location.php';
        if ( CRM_Utils_Array::value('contact_select_id', $formValues ) ) {
            $this->_contactID = $formValues['contact_select_id'][1];
            list( $this->_memberDisplayName, 
                  $this->_memberEmail ) = CRM_Contact_BAO_Contact_Location::getEmailDetails( $this->_contactID );
        }
        
        $params['contact_id'] = $this->_contactID;
        
        $fields = array( 'status_id',
                         'source',
                         'is_override',
                         'campaign_id'
                         );
        
        foreach ( $fields as $f ) {
            $params[$f] = CRM_Utils_Array::value( $f, $formValues );
        }
        
        // fix for CRM-3724
        // when is_override false ignore is_admin statuses during membership 
        // status calculation. similarly we did fix for import in CRM-3570. 
        if ( !CRM_Utils_Array::value( 'is_override', $params ) ) {
            $params['exclude_is_admin'] = true;
        }
        
        // process date params to mysql date format.
        $dateTypes = array( 'join_date'  => 'joinDate',
                            'start_date' => 'startDate',
                            'end_date'   => 'endDate' );
        foreach ( $dateTypes as $dateField => $dateVariable ) {
            $$dateVariable = CRM_Utils_Date::processDate( $formValues[$dateField] );
        }

        $dates = array( 'join_date',
                        'start_date',
                        'end_date',
                        'reminder_date',
                        );
        
        $calcDates = array( );
        foreach( $this->_memTypeSelected as $memType ) {  
            $calcDates[$memType] = CRM_Member_BAO_MembershipType::getDatesForMembershipType($memType,
                                                                                  $joinDate, $startDate, $endDate);
        }
        
        foreach( $calcDates as $memType => $calcDate ) {
            foreach ( $dates as $d ) {
                //first give priority to form values then calDates.
                $date = CRM_Utils_Array::value( $d, $formValues ); 
                if ( !$date ) {
                    $date = CRM_Utils_Array::value( $d, $calcDate );
                }

                $membershipTypeValues[$memType][$d] = CRM_Utils_Date::processDate( $date ); 
                //$params[$d] = CRM_Utils_Date::processDate( $date );
            }
        }
        
        if ( $this->_id ) {
            $ids['membership'] = $params['id'] = $this->_id;
        }
        
        $session = CRM_Core_Session::singleton();
        $ids['userId'] = $session->get('userID');
 
        // membership type custom data
        foreach( $this->_memTypeSelected as $memType ) {  
            $customFields = CRM_Core_BAO_CustomField::getFields( 'Membership', false, false,
                                                                 $memType );
            
            $customFields = CRM_Utils_Array::crmArrayMerge( $customFields,
                                                            CRM_Core_BAO_CustomField::getFields( 'Membership', 
                                                                                                 false, false, 
                                                                                                 null, null, true ) );
            
            $membershipTypeValues[$memType]['custom'] = CRM_Core_BAO_CustomField::postProcess( $formValues,
                                                                                               $customFields,
                                                                                               $this->_id,
                                                                                               'Membership' );
        }

        foreach( $this->_memTypeSelected as $memType ) { 
            $membershipTypes[$memType] = CRM_Core_DAO::getFieldValue( 'CRM_Member_DAO_MembershipType',
                                                                      $memType );
            
        }
        $membershipType = implode(', ', $membershipTypes);


        // Retrieve the name and email of the current user - this will be the FROM for the receipt email
        list( $userName, $userEmail ) = CRM_Contact_BAO_Contact_Location::getEmailDetails( $ids['userId'] );

        if ( CRM_Utils_Array::value( 'record_contribution', $formValues ) ) {
            $recordContribution = array( 'total_amount', 'contribution_type_id', 'payment_instrument_id', 
                                         'trxn_id', 'contribution_status_id', 'check_number', 'campaign_id', 'receive_date' );
            
            foreach ( $recordContribution as $f ) {
                $params[$f] = CRM_Utils_Array::value( $f, $formValues );
            }
            
            if ( !$this->_onlinePendingContributionId ) {
                $params['contribution_source'] = ts('%1 Membership: Offline signup (by %2)',
                                                    array( 1 => $membershipType, 2 => $userName ));
            }
            
            if ( CRM_Utils_Array::value( 'receive_date', $params ) ) {
                $params['receive_date'] = CRM_Utils_Date::processDate( $params['receive_date'] );
            }
            if ( CRM_Utils_Array::value( 'send_receipt', $formValues ) ) {
                $params['receipt_date'] = CRM_Utils_Array::value('receive_date',  $params);
            }
            
            //insert contribution type name in receipt.
            $formValues['contributionType_name'] = CRM_Core_DAO::getFieldValue( 'CRM_Contribute_DAO_ContributionType',
                                                                                $formValues['contribution_type_id'] );
        }

        // process line items, until no previous line items.
        if ( !empty( $lineItem ) ) {
            $params['lineItems'] = $lineItem;
            $params['processPriceSet'] = true;
        }
        
        $createdMemberships =  array( );
        if ( $this->_mode ) {

            if( empty( $formValues['total_amount'] ) ) { 
                // if total amount not provided minimum for membership type is used
                $params['total_amount'] = $formValues['total_amount']  = 
                    CRM_Core_DAO::getFieldValue( 'CRM_Member_DAO_MembershipType', 
                                                 $params['membership_type_id'],'minimum_fee' );
            } else {
                $params['total_amount'] = $formValues['total_amount']  ;
            }
            
            if ( $priceSetId ) {
                $params['contribution_type_id'] = CRM_Core_DAO::getFieldValue( 'CRM_Price_DAO_Set',
                                                                               $priceSetId,
                                                                               'contribution_type_id' );
            } else {
                $params['contribution_type_id'] = CRM_Core_DAO::getFieldValue( 'CRM_Member_DAO_MembershipType', 
                                                                               end($this->_memTypeSelected),
                                                                               'contribution_type_id' );
            }

            require_once 'CRM/Core/BAO/PaymentProcessor.php';
            $this->_paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment( $formValues['payment_processor_id'],
                                                                                  $this->_mode );
            
            //get the payment processor id as per mode.
            $params['payment_processor_id'] = $this->_params['payment_processor_id'] = 
                $formValues['payment_processor_id'] = $this->_paymentProcessor['id'];
            
            require_once "CRM/Contact/BAO/Contact.php";
            
            $now = date( 'YmdHis' );
            $fields = array( );
            
            // set email for primary location.
            $fields["email-Primary"] = 1;
            $formValues["email-5"]   = $formValues["email-Primary"] = $this->_memberEmail;
            $params['register_date'] = $now;
            
            // now set the values for the billing location.
            foreach ( $this->_fields as $name => $dontCare ) {
                $fields[$name] = 1;
            }
            
            // also add location name to the array
            $formValues["address_name-{$this->_bltID}"] =
                CRM_Utils_Array::value( 'billing_first_name' , $formValues ) . ' ' .
                CRM_Utils_Array::value( 'billing_middle_name', $formValues ) . ' ' .
                CRM_Utils_Array::value( 'billing_last_name'  , $formValues );
            
            $formValues["address_name-{$this->_bltID}"] = trim( $formValues["address_name-{$this->_bltID}"] );
        
            $fields["address_name-{$this->_bltID}"] = 1;
            
            $fields["email-{$this->_bltID}"]        = 1;
            
            $ctype = CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_Contact', $this->_contactID, 'contact_type' );
            
            $nameFields = array( 'first_name', 'middle_name', 'last_name' );
            
            foreach ( $nameFields as $name ) {
                $fields[$name] = 1;
                if ( array_key_exists( "billing_$name", $formValues ) ) {
                    $formValues[$name]             = $formValues["billing_{$name}"];
                    $formValues['preserveDBName'] = true;
                }
            }
            
            $contactID = CRM_Contact_BAO_Contact::createProfileContact( $formValues, $fields, 
                                                                        $this->_contactID, null, null, $ctype );
            
            // add all the additioanl payment params we need
            $this->_params["state_province-{$this->_bltID}"] = $this->_params["billing_state_province-{$this->_bltID}"] =
                CRM_Core_PseudoConstant::stateProvinceAbbreviation( $this->_params["billing_state_province_id-{$this->_bltID}"] );
            $this->_params["country-{$this->_bltID}"] = $this->_params["billing_country-{$this->_bltID}"] =
                CRM_Core_PseudoConstant::countryIsoCode( $this->_params["billing_country_id-{$this->_bltID}"] );
            
            $this->_params['year'      ]     = $this->_params['credit_card_exp_date']['Y'];
            $this->_params['month'     ]     = $this->_params['credit_card_exp_date']['M'];
            $this->_params['ip_address']     = CRM_Utils_System::ipAddress( );
            $this->_params['amount'        ] = $params['total_amount'];
            $this->_params['currencyID'    ] = $config->defaultCurrency;
            $this->_params['payment_action'] = 'Sale';
            $this->_params['invoiceID']      = md5( uniqid( rand( ), true ) );
            $this->_params['contribution_type_id'] = $params['contribution_type_id'];
        
            // at this point we've created a contact and stored its address etc
            // all the payment processors expect the name and address to be in the 
            // so we copy stuff over to first_name etc. 
            $paymentParams = $this->_params;
            
            if ( CRM_Utils_Array::value( 'send_receipt', $this->_params ) ) {
                $paymentParams['email'] = $this->_memberEmail;
            }
            
            require_once 'CRM/Core/Payment/Form.php';
            CRM_Core_Payment_Form::mapParams( $this->_bltID, $this->_params, $paymentParams, true );
            
            // CRM-7137 -for recurring membership, 
            // we do need contribution and recuring records. 
            $result = null;
            if ( CRM_Utils_Array::value( 'is_recur', $paymentParams ) ) {
                $allStatus = CRM_Member_PseudoConstant::membershipStatus( );

                require_once 'CRM/Contribute/Form/Contribution/Confirm.php';
                $contribution 
                    = CRM_Contribute_Form_Contribution_Confirm::processContribution( $this, 
                                                                                     $this->_params, 
                                                                                     $result, 
                                                                                     $contactID, 
                                                                                     $params['contribution_type_id'],  
                                                                                     false,
                                                                                     true, 
                                                                                     false );
                $paymentParams['contactID']           = $contactID;
                $paymentParams['contributionID'    ]  = $contribution->id;
                $paymentParams['contributionTypeID']  = $contribution->contribution_type_id;
                $paymentParams['contributionPageID']  = $contribution->contribution_page_id;
                $paymentParams['contributionRecurID'] = $contribution->contribution_recur_id;
                $ids['contribution']                  = $contribution->id;
                $params['contribution_recur_id']      = $paymentParams['contributionRecurID'];
                $params['status_id']                  = array_search( 'Pending', $allStatus );
                $params['skipStatusCal']              = true;
               
                //as membership is pending set dates to null.
                $memberDates = array( 'join_date'  => 'joinDate',
                                      'start_date' => 'startDate',
                                      'end_date'   => 'endDate' );
                
                foreach ($memberDates as $dp => $dv) {
                    $$dv = null;
                    foreach( $this->_memTypeSelected as $memType ) {  
                        $membershipTypeValues[$memType][$dv] = null;
                    }
                }
            }
            
            $payment =& CRM_Core_Payment::singleton( $this->_mode, $this->_paymentProcessor, $this );
            
            $result  =& $payment->doDirectPayment( $paymentParams );

            if ( is_a( $result, 'CRM_Core_Error' ) ) {
                //make sure to cleanup db for recurring case.
                if ( CRM_Utils_Array::value( 'contributionID', $paymentParams ) ) {
                    require_once 'CRM/Contribute/BAO/Contribution.php';
                    CRM_Contribute_BAO_Contribution::deleteContribution( $paymentParams['contributionID'] );
                }
                if ( CRM_Utils_Array::value( 'contributionRecurID', $paymentParams ) ) {
                    require_once 'CRM/Contribute/BAO/ContributionRecur.php';
                    CRM_Contribute_BAO_ContributionRecur::deleteRecurContribution( $paymentParams['contributionRecurID'] );
                }
                
                CRM_Core_Error::displaySessionError( $result );
                CRM_Utils_System::redirect( CRM_Utils_System::url( 'civicrm/contact/view/membership',
                                                                   "reset=1&action=add&cid={$this->_contactID}&context=&mode={$this->_mode}" ) );
            }
            
            if ( $result ) {
                $this->_params = array_merge( $this->_params, $result );

                //assign amount to template if payment was successful
                $this->assign( 'amount', $params['total_amount'] );			
            }
            $params['contribution_status_id'] = CRM_Utils_Array::value( 'is_recur', $paymentParams ) ? 2 : 1;
            $params['receive_date']           = $now;
            $params['invoice_id']             = $this->_params['invoiceID'];
            $params['contribution_source']    = ts('%1 Membership Signup: Credit card or direct debit (by %2)',
                                                                     array( 1 => $membershipType, 2 => $userName));
            $params['source']                 = $formValues['source'] ? $formValues['source'] :$params['contribution_source'];
            $params['trxn_id']                = $result['trxn_id'];
            $params['payment_instrument_id']  = 1;
            $params['is_test']                = ( $this->_mode == 'live' ) ? 0 : 1 ; 
            if ( CRM_Utils_Array::value( 'send_receipt', $this->_params ) ) {
                $params['receipt_date'] = $now;
            } else {
                $params['receipt_date'] = null;
            }
            
            $this->set( 'params', $this->_params );
            $this->assign( 'trxn_id', $result['trxn_id'] );
            $this->assign( 'receive_date',
                           CRM_Utils_Date::mysqlToIso( $params['receive_date']) );
       
            // required for creating membership for related contacts
            $params['action'] = $this->_action;

            //create membership record.
            $count = 0;
            foreach( $this->_memTypeSelected as $memType ) {
                if ( $count && 
                     ($relateContribution = CRM_Member_BAO_Membership::getMembershipContributionId($membership->id)) ) {
                    $membershipTypeValues[$memType]['relate_contribution_id'] = $relateContribution;
                }

                $membershipParams = array_merge($membershipTypeValues[$memType],$params);
                $membership = CRM_Member_BAO_Membership::create($membershipParams, $ids);

                $createdMemberships[$memType] = $membership;
                $count++;
            }
            
            if ( !CRM_Utils_Array::value( 'is_recur', $params ) ) {
                $contribution = new CRM_Contribute_BAO_Contribution();
                $contribution->trxn_id = $result['trxn_id'];
                if ( $contribution->find( true ) ) {
                    // next create the transaction record
                    $trxnParams = array(
                                        'contribution_id'   => $contribution->id,
                                        'trxn_date'         => $now,
                                        'trxn_type'         => 'Debit',
                                        'total_amount'      => $params['total_amount'],
                                        'fee_amount'        => CRM_Utils_Array::value( 'fee_amount', $result ),
                                        'net_amount'        => CRM_Utils_Array::value( 'net_amount', $result, $params['total_amount'] ),
                                        'currency'          => $config->defaultCurrency,
                                        'payment_processor' => $this->_paymentProcessor['payment_processor_type'],
                                        'trxn_id'           => $result['trxn_id'],
                                        );
                    
                    require_once 'CRM/Core/BAO/FinancialTrxn.php';
                    $trxn =& CRM_Core_BAO_FinancialTrxn::create( $trxnParams );
                }
            }
        } else {
            $params['action'] = $this->_action;
            if ( $this->_onlinePendingContributionId && 
                 CRM_Utils_Array::value( 'record_contribution', $formValues ) ) {
                
                // update membership as well as contribution object, CRM-4395
                require_once 'CRM/Contribute/Form/Contribution.php';
                $params['contribution_id'] = $this->_onlinePendingContributionId;
                $params['componentId']     = $params['id'];
                $params['componentName']   = 'contribute';
                $result = CRM_Contribute_BAO_Contribution::transitionComponents( $params, true );
                
                //carry updated membership object.
                $membership = new CRM_Member_DAO_Membership( );
                $membership->id = $this->_id;
                $membership->find( true );
                
                $cancelled = true;
                if ( $membership->end_date ) { 
                    //display end date w/ status message.
                    $endDate = $membership->end_date;
                    
                    require_once 'CRM/Member/PseudoConstant.php';
                    $membershipStatues = CRM_Member_PseudoConstant::membershipStatus( );
                    if ( !in_array( $membership->status_id, array( array_search('Cancelled',$membershipStatues),
                                                                   array_search('Expired',$membershipStatues) ) ) ) {   
                        $cancelled = false;
                    }
                }
                // suppress form values in template.
                $this->assign( 'cancelled', $cancelled );
                
                // FIX ME: need to recheck this
                // here we might updated dates, so get from object.
                foreach ( $calcDates[$membership->membership_type_id] as $date => &$val ) {
                    if ( $membership->$date ) {
                    	$val = $membership->$date;
                    }
                }
                
                $createdMemberships[] = $membership;

            } else {
                $count = 0;
                foreach( $this->_memTypeSelected as $memType ) {
                    if ( $count && 
                         CRM_Utils_Array::value( 'record_contribution', $formValues ) &&
                         ($relateContribution = CRM_Member_BAO_Membership::getMembershipContributionId($membership->id))
                         ) {
                        $membershipTypeValues[$memType]['relate_contribution_id'] = $relateContribution;
                    }
                    $membershipParams = array_merge($params, $membershipTypeValues[$memType]);
                    $membership = CRM_Member_BAO_Membership::create($membershipParams, $ids);
                    $createdMemberships[$memType] = $membership;
                    $count++;
                }
            }
        }
        
        if ( !empty($lineItem) ) {
            foreach($lineItem[$priceSetId] as &$priceFieldOp) {
                if ( CRM_Utils_Array::value( 'membership_type_id', $priceFieldOp) ) {
                    $priceFieldOp['start_date'] = $membershipTypeValues[$priceFieldOp['membership_type_id']]['start_date'] ? CRM_Utils_Date::customFormat($membershipTypeValues[$priceFieldOp['membership_type_id']]['start_date'], '%d%f %b, %Y') : '-';
                    
                    $priceFieldOp['end_date'] = $membershipTypeValues[$priceFieldOp['membership_type_id']]['end_date'] ? CRM_Utils_Date::customFormat($membershipTypeValues[$priceFieldOp['membership_type_id']]['end_date'], '%d%f %b, %Y') : '-';
                } else {
                    $priceFieldOp['start_date'] = $priceFieldOp['end_date'] = 'N/A';
                }
            }
        }
      
        $this->assign( 'lineItem', !empty( $lineItem ) ? $lineItem : false );

        $receiptSend = false;
        if ( CRM_Utils_Array::value( 'send_receipt', $formValues ) ) {
            $receiptSend = true;
            // retrieve 'from email id' for acknowledgement
            $receiptFrom = $formValues['from_email_address'];
            
            if ( CRM_Utils_Array::value( 'payment_instrument_id', $formValues ) ) {
                $paymentInstrument    = CRM_Contribute_PseudoConstant::paymentInstrument();
                $formValues['paidBy'] = $paymentInstrument[$formValues['payment_instrument_id']];
            }

            // retrieve custom data
            require_once "CRM/Core/BAO/UFGroup.php";
            $customFields = $customValues = array( );
            foreach ( $this->_groupTree as $groupID => $group ) {
                if ( $groupID == 'info' ) {
                    continue;
                }
                foreach ( $group['fields'] as $k => $field ) {
                    $field['title'] = $field['label'];
                    $customFields["custom_{$k}"] = $field;
                }
            }
            $members = array( array( 'member_id', '=', $membership->id, 0, 0 ) );
            // check whether its a test drive 
            if ( $this->_mode == 'test' ) {
                $members[] = array( 'member_test', '=', 1, 0, 0 ); 
            } 
            CRM_Core_BAO_UFGroup::getValues( $this->_contactID, $customFields, $customValues , false, $members );
            if ( $this->_mode ) {
                if ( CRM_Utils_Array::value( 'billing_first_name', $this->_params ) ) {
                    $name = $this->_params['billing_first_name'];
                }
                
                if ( CRM_Utils_Array::value( 'billing_middle_name', $this->_params ) ) {
                    $name .= " {$this->_params['billing_middle_name']}";
                }
                
                if ( CRM_Utils_Array::value( 'billing_last_name', $this->_params ) ) {
                    $name .= " {$this->_params['billing_last_name']}";
                }
                $this->assign( 'billingName', $name );
                
                // assign the address formatted up for display
                $addressParts  = array( "street_address-{$this->_bltID}",
                                        "city-{$this->_bltID}",
                                        "postal_code-{$this->_bltID}",
                                        "state_province-{$this->_bltID}",
                                        "country-{$this->_bltID}");
                $addressFields = array( );
                foreach ($addressParts as $part) {
                    list( $n, $id ) = explode( '-', $part );
                    if ( isset ( $this->_params['billing_' . $part] ) ) {
                        $addressFields[$n] = $this->_params['billing_'.$part];
                    }
                }
                require_once 'CRM/Utils/Address.php';
                $this->assign('address', CRM_Utils_Address::format( $addressFields ) );

                $date = CRM_Utils_Date::format( $this->_params['credit_card_exp_date'] );
                $date = CRM_Utils_Date::mysqlToIso( $date );
                $this->assign( 'credit_card_exp_date', $date );
                $this->assign( 'credit_card_number',
                               CRM_Utils_System::mungeCreditCard( $this->_params['credit_card_number'] ) );
                $this->assign( 'credit_card_type', $this->_params['credit_card_type'] );
                $this->assign( 'contributeMode', 'direct');
                $this->assign( 'isAmountzero' , 0);
                $this->assign( 'is_pay_later',0);
                $this->assign( 'isPrimary', 1 );
            }
            $this->assign( 'module', 'Membership' );
            $this->assign( 'contactID', $this->_contactID );
            $this->assign( 'membershipID', CRM_Utils_Array::value('membership_id', $params,CRM_Utils_Array::value('membership_id',$this->_defaultValues)));
            $this->assign( 'contributionID', isset($contribution)? $contribution->id : null );
            $this->assign('receiptType', 'membership signup');
            $this->assign( 'receive_date', CRM_Utils_Array::value('receive_date', $params) );            
            $this->assign( 'formValues', $formValues );

            if ( empty($lineItem) ) {
                $this->assign( 'mem_start_date', CRM_Utils_Date::customFormat($calcDates[$membership->membership_type_id]['start_date']) );
                $this->assign( 'mem_end_date', CRM_Utils_Date::customFormat($calcDates[$membership->membership_type_id]['end_date']) );
                $this->assign( 'membership_name', $membershipType );
            }

            $this->assign( 'customValues', $customValues );

            require_once 'CRM/Core/BAO/MessageTemplates.php';
            list ($mailSend, $subject, $message, $html) = CRM_Core_BAO_MessageTemplates::sendTemplate(
                array(
                    'groupName' => 'msg_tpl_workflow_membership',
                    'valueName' => 'membership_offline_receipt',
                    'contactId' => $this->_contactID,
                    'from'      => $receiptFrom,
                    'toName'    => $this->_memberDisplayName,
                    'toEmail'   => $this->_memberEmail,
                    'isTest'    => (bool) ($this->_action & CRM_Core_Action::PREVIEW),
                )
            );
        }
        
        
        if ( ( $this->_action & CRM_Core_Action::UPDATE ) ) {
            //end date can be modified by hooks, so if end date is set then use it. 
            $endDate = ( $membership->end_date ) ? $membership->end_date : $endDate ;
 
            $statusMsg = ts('Membership for %1 has been updated.', array(1 => $this->_memberDisplayName));
            if ( $endDate && $endDate !== 'null' ) {
                $endDate=CRM_Utils_Date::customFormat($endDate);
                $statusMsg .= ' '.ts('The membership End Date is %1.', array(1 => $endDate));
            }
            if ( $receiptSend ) {
                $statusMsg .= ' '.ts('A confirmation and receipt has been sent to %1.', array(1 => $this->_memberEmail));
            }
        } elseif ( ( $this->_action & CRM_Core_Action::ADD ) ) {
            // FIX ME: fix status messages

            $statusMsg = array();
            foreach ( $membershipTypes as $memType => $membershipType ) {
                $statusMsg[$memType] = ts('%1 membership for %2 has been added.', array(1 => $membershipType, 2 => $this->_memberDisplayName));
                
                $membership = $createdMemberships[$memType];
                $memEndDate = ( $membership->end_date ) ? $membership->end_date : $endDate;
                
                //get the end date from calculated dates. 
                if ( !$memEndDate && !CRM_Utils_Array::value( 'is_recur', $params ) ) {
                    $memEndDate = CRM_Utils_Array::value( 'end_date', $calcDates[$memType] ); 
                }
            
                if ( $memEndDate && $memEndDate !== 'null' ) {
                    $memEndDate = CRM_Utils_Date::customFormat($memEndDate);
                    $statusMsg[$memType] .= ' '.ts('The new membership End Date is %1.', array(1 => $memEndDate));
                }
            }
            $statusMsg = implode('<br/>',  $statusMsg);
            if ( $receiptSend && $mailSend ) {
                $statusMsg .= '<br/>'.ts('A membership confirmation and receipt has been sent to %1.', array(1 => $this->_memberEmail));
            }
        }
        CRM_Core_Session::setStatus($statusMsg);
        
        $buttonName = $this->controller->getButtonName( );
        if ( $this->_context == 'standalone' ) {
            if ( $buttonName == $this->getButtonName( 'upload', 'new' ) ) {
                $session->replaceUserContext(CRM_Utils_System::url('civicrm/member/add', 
                                                                   'reset=1&action=add&context=standalone') );
            } else {
                $session->replaceUserContext(CRM_Utils_System::url( 'civicrm/contact/view',
                                                                    "reset=1&cid={$this->_contactID}&selectedChild=member" ) );
            }
        } else if ( $buttonName == $this->getButtonName( 'upload', 'new' ) ) {
            $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view/membership', 
                                                               "reset=1&action=add&context=membership&cid={$this->_contactID}") );
        }
    }
}

