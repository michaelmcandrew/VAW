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

require_once 'CRM/Core/Form.php';
require_once 'CRM/Contribute/Import/Parser.php';

/**
 * This class summarizes the import results
 */
class CRM_Contribute_Import_Form_Summary extends CRM_Core_Form 
{

    /**
     * Function to set variables up before form is built
     *
     * @return void
     * @access public
     */
    public function preProcess( ) 
    {
        // set the error message path to display
        $errorFile = $this->assign('errorFile', $this->get('errorFile') );
        
        $totalRowCount = $this->get('totalRowCount');
        $relatedCount = $this->get('relatedCount');
        $totalRowCount += $relatedCount;
        $this->set('totalRowCount', $totalRowCount);

        $invalidRowCount = $this->get('invalidRowCount');
        $invalidSoftCreditRowCount = $this->get('invalidSoftCreditRowCount');
        if ( $invalidSoftCreditRowCount ) {
            $urlParams = 'type='.CRM_Contribute_Import_Parser::SOFT_CREDIT_ERROR . '&parser=CRM_Contribute_Import_Parser';
            $this->set('downloadSoftCreditErrorRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams ) );
        }
        $validSoftCreditRowCount = $this->get('validSoftCreditRowCount');
        $invalidPledgePaymentRowCount = $this->get('invalidPledgePaymentRowCount');
        if ( $invalidPledgePaymentRowCount ) {
            $urlParams = 'type='.CRM_Contribute_Import_Parser::PLEDGE_PAYMENT_ERROR.'&parser=CRM_Contribute_Import_Parser';
            $this->set('downloadPledgePaymentErrorRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams ) );
        }
        $validPledgePaymentRowCount   = $this->get('validPledgePaymentRowCount');
        $conflictRowCount = $this->get('conflictRowCount');
        $duplicateRowCount = $this->get('duplicateRowCount');
        $onDuplicate = $this->get('onDuplicate');
        $mismatchCount      = $this->get('unMatchCount');
        if ($duplicateRowCount > 0) {
            $urlParams = 'type='.CRM_Contribute_Import_Parser::DUPLICATE . '&parser=CRM_Contribute_Import_Parser';
            $this->set('downloadDuplicateRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams ) );
        }else if($mismatchCount) {
            $urlParams = 'type='.CRM_Contribute_Import_Parser::NO_MATCH . '&parser=CRM_Contribute_Import_Parser';
            $this->set('downloadMismatchRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams ) );
        } else {
            $duplicateRowCount = 0;
            $this->set('duplicateRowCount', $duplicateRowCount);
        }

        $this->assign('dupeError', false);
        
        if ($onDuplicate == CRM_Contribute_Import_Parser::DUPLICATE_UPDATE) {
            $dupeActionString = 
                ts('These records have been updated with the imported data.');   
        } else if ($onDuplicate == CRM_Contribute_Import_Parser::DUPLICATE_FILL) {
            $dupeActionString =
                ts('These records have been filled in with the imported data.');
        } else {
            /* Skip by default */
            $dupeActionString = 
                ts('These records have not been imported.');

            $this->assign('dupeError', true);
        
            /* only subtract dupes from succesful import if we're skipping */
            $this->set('validRowCount', $totalRowCount - $invalidRowCount -
                    $conflictRowCount - $duplicateRowCount - $mismatchCount - $invalidSoftCreditRowCount - $invalidPledgePaymentRowCount );
        }
        $this->assign('dupeActionString', $dupeActionString);
        
        $properties = array( 'totalRowCount', 'validRowCount', 'invalidRowCount', 'validSoftCreditRowCount', 'invalidSoftCreditRowCount', 'conflictRowCount', 'downloadConflictRecordsUrl', 'downloadErrorRecordsUrl', 'duplicateRowCount', 'downloadDuplicateRecordsUrl','downloadMismatchRecordsUrl', 'groupAdditions', 'unMatchCount', 'validPledgePaymentRowCount', 'invalidPledgePaymentRowCount', 'downloadPledgePaymentErrorRecordsUrl', 'downloadSoftCreditErrorRecordsUrl' );
        foreach ( $properties as $property ) {
            $this->assign( $property, $this->get( $property ) );
        }
    }

    /**
     * Function to actually build the form
     *
     * @return None
     * @access public
     */
    public function buildQuickForm( ) 
    {
        $this->addButtons( array(
                                 array ( 'type'      => 'next',
                                         'name'      => ts('Done'),
                                         'isDefault' => true   ),
                                 )
                           );
    }

    /**
     * Return a descriptive name for the page, used in wizard header
     *
     * @return string
     * @access public
     */
    public function getTitle( ) 
    {
        return ts('Summary');
    }

}


