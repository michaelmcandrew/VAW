<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.4		                         				  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011							      |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.								      |
 |																      |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License			  |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.	  |
 |																	  |
 | CiviCRM is distributed in the hope that it will be useful, but	  |
 | WITHOUT ANY WARRANTY; without even the implied warranty of	      |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.				  |
 | See the GNU Affero General Public License for more details.		  |
 |																	  |
 | You should have received a copy of the GNU Affero General Public	  |
 | License and the CiviCRM Licensing Exception along				  |
 | with this program; if not, contact CiviCRM LLC					  |
 | at info[AT]civicrm[DOT]org. If you have questions about the		  |
 | GNU Affero General Public License or the licensing of CiviCRM,	  |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing		  |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */

require_once 'CRM/Report/Form.php';
require_once 'CRM/Event/PseudoConstant.php';
require_once 'CRM/Core/OptionGroup.php';
require_once 'CRM/Event/BAO/Participant.php';

class CRM_Report_Form_Event_ParticipantListCount extends CRM_Report_Form {
    
	protected $_summary = null;
    
    protected $_customGroupExtends = array( 'Participant' );

    function __construct( ) {
		$this->_columns = 
			array( 
                  'civicrm_contact'		=> 
                  array('dao'			=> 'CRM_Contact_DAO_Contact',
                        'fields'		=> 
                        array('sort_name'               =>
                              array('title'			=> ts( 'Name' ),
                                    'default'		=> true,
                                    'no_repeat'		=> true,
                                    'required'		=> true, ),
                              'id'			      	    =>
                              array('no_display'	=> true,
                                    'required'		=> true, ), ),
                        'grouping'	=> 'contact-fields',	
                        'filters'	=> array('sort_name' => 
                                             array('title'			=> ts( 'Participant Name' ),
                                                   'operator'		=> 'like', ), ), ),
                  
                  'civicrm_email'		=>
                  array('dao'			=> 'CRM_Core_DAO_Email',
                        'fields'		=> array('email' =>
                                                 array( 'title'	    => ts( 'Email' ),
                                                        'no_repeat'	=> true, ), ),
                        'grouping'	=> 'contact-fields',
                        'filters'	=> 
                        array('email'    	            =>
                              array( 'title'	    => ts( 'Participant E-mail' ),
                                     'operator'		=> 'like', ), ), ),
                  
                  'civicrm_address'		=> 
                  array('dao'			=> 'CRM_Core_DAO_Address',
                        'fields'		=> 
                        array('street_address'		=> null, ),
                        'grouping'	=>	'contact-fields', ),
                  'civicrm_participant'=> 
                  array('dao'			=> 'CRM_Event_DAO_Participant',
                        'fields'		=> 
                        array('participant_id'	=>
                              array('title'			=> ts( 'Participant ID' ),
                                    'default'		=> true, ),
                              'event_id'				   =>
                              array('title'			=> ts( 'Event' ),
                                    'type'			=> CRM_Utils_Type::T_STRING, ),
                              'role_id'						=> 
                              array('title'			=> ts( 'Role' ),
                                    'default'		=> true, ),
                              'status_id'						=> 
                              array('title'			=> ts( 'Status' ),
                                    'default'		=> true, ),
                              'participant_register_date'=> 
                              array('title'			=> ts( 'Registration Date' ), ), ), 
                        'grouping'	=> 'event-fields',
                        'filters'	=> 
                        array('event_id'		=>
                              array('name'			=> 'event_id',
                                    'title'			=> ts( 'Event' ),
                                    'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                    'options'		=> CRM_Event_PseudoConstant::event( null, null, "is_template IS NULL OR is_template = 0" ), ),
                              'sid'								=> 
                              array('name'			=> 'status_id',
                                    'title'			=> ts( 'Participant Status' ),
                                    'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                    'options'		=> CRM_Event_PseudoConstant::participantStatus( null, null, 'label' ), ),
                              'rid'								=> 
                              array('name'			=> 'role_id',
                                    'title'			=> ts( 'Participant Role' ),
                                    'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                    'options'		=> CRM_Event_PseudoConstant::participantRole( ), ),
                              'participant_register_date'=> array('title'			=> ts( 'Registration Date' ),
                                                                  'operatorType' => CRM_Report_Form::OP_DATE, ), ),
                        'group_bys' => 
                        array('event_id'						=>
                              array('title'			=> ts( 'Event' ), ), ), ),
                  
                  'civicrm_event'		=> 
                  array('dao'			=> 'CRM_Event_DAO_Event',
                        'fields'		=> 
                        array('event_type_id'				=> 
                              array('title'			=> ts('Event Type'), ), 
                              'start_date'					=> 
                              array('title'			=> ts('Event Start Date'), ),
                              'end_date'				=>
                              array('title'			=> ts('Event End Date'), ), ),
                        'grouping'	=> 'event-fields', 
                        'filters'	=> array('eid'								=>
                                             array('name'			=> 'event_type_id',
                                                   'title'			=> ts( 'Event Type' ),
                                                   'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                                   'options'		=> CRM_Core_OptionGroup::values('event_type'), ), 
                                             'event_start_date'			=> 
                                             array('name'			=> 'start_date',
                                                   'title'			=> ts( 'Event Start Date' ),
                                                   'operatorType' => CRM_Report_Form::OP_DATE, ),
                                             'event_end_date'				=> 
                                             array('name'			=> 'end_date',
                                                   'title'			=> ts( 'Event End Date' ),
                                                   'operatorType' => CRM_Report_Form::OP_DATE, ), ),
                        'group_bys' => 
                        array('event_type_id'				=> 
                              array('title'			=> ts( 'Event Type ' ), ), ), ),
                  'civicrm_line_item'	=> 
                  array('dao'			=> 'CRM_Price_DAO_LineItem',
                        'fields'		=>
                        array('line_total'					=>
                              array('title'		  => ts( 'Income') ,
                                    'default'	  => true,
                                    'statistics'  => 
                                    array('sum'   => ts( 'Amount' ),
                                          'avg'   => ts('Average' ), ), ),
                              'participant_count'			=> 
                              array('title'		  => ts( 'Count' ),
                                    'default'	  => true,
                                    'statistics'  => 
                                    array('sum'   => ts( 'Count' ), ), ), ), ),
                   );
        
        $this->_options = 
            array(
                  'blank_column_begin' => 
                  array('title'	=> ts('Blank column at the Begining'),
                        'type'	=> 'checkbox', ),
                  'blank_column_end'	=> 
                  array('title'	=> ts('Blank column at the End'),
                        'type'	=> 'select',
                        'options'=> array('' => '-select-',
                                          1	 => ts( 'One' ),
                                          2	 => ts( 'Two' ),
                                          3	 => ts( 'Three' ),
                                          ), ),
                  );
        parent::__construct( );
    }
    
    
	
	function preProcess( ) {
		parent::preProcess( );
	}
    
	//Add The statistics
	function statistics( &$rows ) {
		
        $statistics = parent::statistics( $rows );
        $avg = null;
        $select	=	" SELECT SUM( {$this->_aliases['civicrm_line_item']}.participant_count ) as count,
									SUM( {$this->_aliases['civicrm_line_item']}.line_total )	 as amount
						";
        $sql = "{$select} {$this->_from} {$this->_where}";
		$dao = CRM_Core_DAO::executeQuery( $sql );
		if ( $dao->fetch( ) ) {
            
			if($dao->count && $dao->amount ) {
				$avg = $dao->amount / $dao->count;
			}	
            $statistics['counts']['count']	 = array( 'value' => $dao->count,
                                                      'title' => 'Total Participants',
                                                      'type'	=> CRM_Utils_Type::T_INT );	 
			$statistics['counts']['amount']	 = array( 'value' => $dao->amount,
                                                      'title' => 'Total Income',
                                                      'type'	=> CRM_Utils_Type::T_MONEY );
			$statistics['counts']['avg	  ']	 = array( 'value' => $avg,
                                                          'title' => 'Average',
                                                          'type'	=> CRM_Utils_Type::T_MONEY );
		}
        
        return $statistics;
	}
    
    function select( ) {
        $select = array( );
        $this->_columnHeaders = array( );
		
        //add blank column at the Start
        if ( CRM_Utils_Array::value( 'blank_column_begin', $this->_params['options'] ) ) {
            $select[] = " '' as blankColumnBegin";
            $this->_columnHeaders['blankColumnBegin']['title'] = '_ _ _ _';
        }
        foreach ( $this->_columns as $tableName => $table ) {
            if ( array_key_exists('fields', $table) ) {
                foreach ( $table['fields'] as $fieldName => $field ) {
                    if ( CRM_Utils_Array::value( 'required', $field ) ||
                         CRM_Utils_Array::value( $fieldName, $this->_params['fields'] ) ) {
                        if ( CRM_Utils_Array::value('statistics', $field) ) {
                            foreach ( $field['statistics'] as $stat => $label ) {
                                switch (strtolower($stat)) {
                                case 'sum':
                                    $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_INT;
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                                    break;
                                }
                            }	  
                        }else{								
                            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
                            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type']	= CRM_Utils_Array::value( 'type', $field );
                            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
                        }
                    }
                }
            }
        }
        //add blank column at the end
        if ( $blankcols = CRM_Utils_Array::value( 'blank_column_end', $this->_params ) ) {
            for ( $i= 1; $i <= $blankcols; $i++ ) {
                $select[] = " '' as blankColumnEnd_{$i}";
                $this->_columnHeaders["blank_{$i}"]['title'] = "_ _ _ _";
            }
        }
        $this->_select = "SELECT " . implode( ', ', $select ) . " ";
    }
    
    static function formRule( $fields, $files, $self ) {	 
        $errors = $grouping = array( );
        return $errors;
    }
    
    function from( ) {
        $this->_from = "
		  FROM civicrm_participant {$this->_aliases['civicrm_participant']}
				 LEFT JOIN civicrm_event {$this->_aliases['civicrm_event']} 
						  ON ({$this->_aliases['civicrm_event']}.id = {$this->_aliases['civicrm_participant']}.event_id ) AND ({$this->_aliases['civicrm_event']}.is_template IS NULL OR {$this->_aliases['civicrm_event']}.is_template = 0)
				 LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']} 
						  ON ({$this->_aliases['civicrm_participant']}.contact_id  = {$this->_aliases['civicrm_contact']}.id	)
				 {$this->_aclFrom}
				 LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
						  ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id AND 
							  {$this->_aliases['civicrm_address']}.is_primary = 1 
				 LEFT JOIN	civicrm_email {$this->_aliases['civicrm_email']} 
						  ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND
							  {$this->_aliases['civicrm_email']}.is_primary = 1) 
				 LEFT JOIN civicrm_line_item {$this->_aliases['civicrm_line_item']}
						  ON {$this->_aliases['civicrm_participant']}.id ={$this->_aliases['civicrm_line_item']}.entity_id AND {$this->_aliases['civicrm_line_item']}.entity_table = 'civicrm_participant'";
    }
    
    function where( ) {
        $clauses = array( );
        foreach ( $this->_columns as $tableName => $table ) {
            if ( array_key_exists('filters', $table) ) { 
                foreach ( $table['filters'] as $fieldName => $field ) {
                    $clause = null;
                    if ( CRM_Utils_Array::value( 'type', $field ) & CRM_Utils_Type::T_DATE ) {
                        $relative = CRM_Utils_Array::value( "{$fieldName}_relative", $this->_params );
                        $from		 = CRM_Utils_Array::value( "{$fieldName}_from"	  , $this->_params );
                        $to		 = CRM_Utils_Array::value( "{$fieldName}_to"		  , $this->_params );
                        
                        if ( $relative || $from || $to ) {
                            $clause = $this->dateClause( $field['name'], $relative, $from, $to, $field['type'] );
                        }
                    } else { 
                        $op = CRM_Utils_Array::value( "{$fieldName}_op", $this->_params );
                        
                        if ( $fieldName == 'rid' ) {
                            $value =  CRM_Utils_Array::value("{$fieldName}_value", $this->_params);
                            if ( !empty($value) ) {
                                $clause = "( {$field['dbAlias']} REGEXP '[[:<:]]" . implode( '[[:>:]]|[[:<:]]',  $value ) . "[[:>:]]' )";
                            }
                            $op = null;
                        }

                        if ( $op ) {
                            $clause = 
                                $this->whereClause( $field,
                                                    $op,
                                                    CRM_Utils_Array::value( "{$fieldName}_value", $this->_params ),
                                                    CRM_Utils_Array::value( "{$fieldName}_min", $this->_params ),
                                                    CRM_Utils_Array::value( "{$fieldName}_max", $this->_params ) );
                        }
                    }
					
                    if ( ! empty( $clause ) ) {
                        $clauses[] = $clause;
                    }
                }
            }
        }
        
        if ( empty( $clauses ) ) {
            $this->_where = "WHERE {$this->_aliases['civicrm_participant']}.is_test = 0 ";
		  } else {
            $this->_where = "WHERE {$this->_aliases['civicrm_participant']}.is_test = 0 AND " . implode( ' AND ', $clauses );
        }
        if ( $this->_aclWhere ) {
              $this->_where .= " AND {$this->_aclWhere} ";
        }
    }
    
    function groupBy( ) {
        $this->_groupBy = "";
        if ( CRM_Utils_Array::value( 'group_bys', $this->_params ) &&
             is_array($this->_params['group_bys']) &&
             !empty($this->_params['group_bys']) ) {
            foreach ( $this->_columns as $tableName => $table ) {
                if ( array_key_exists('group_bys', $table) ) {
                    foreach ( $table['group_bys'] as $fieldName => $field ) {
								if ( CRM_Utils_Array::value( $fieldName, $this->_params['group_bys'] ) ) {
                                    $this->_groupBy[] = $field['dbAlias'];
								}
                    }
                }
            }
        } 
        
        if ( !empty( $this->_groupBy ) ) {
            $this->_groupBy = "ORDER BY " . implode( ', ', $this->_groupBy )	. ", {$this->_aliases['civicrm_contact']}.sort_name";
        } else {
            $this->_groupBy = "ORDER BY {$this->_aliases['civicrm_contact']}.sort_name";
        }
        $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_participant']}.id " . $this->_groupBy;
    }
    
    function postProcess( ) {
        
        // get ready with post process params
        $this->beginPostProcess( );
        
        // get the acl clauses built before we assemble the query
        $this->buildACLClause( $this->_aliases['civicrm_contact'] );
        // build query
        $sql = $this->buildQuery( true );
        
        // build array of result based on column headers. This method also allows 
        // modifying column headers before using it to build result set i.e $rows.
        $this->buildRows ( $sql, $rows );
        
        // format result set. 
        $this->formatDisplay( $rows );
        
        // assign variables to templates
        $this->doTemplateAssignment( $rows );
        
        // do print / pdf / instance stuff if needed
        $this->endPostProcess( $rows );
        
		
    }
    
    function alterDisplay( &$rows ) {
        
        $entryFound = false;
        $eventType  = CRM_Core_OptionGroup::values('event_type');
		
        foreach ( $rows as $rowNum => $row ) {
            
            // convert sort name to links
            if ( array_key_exists('civicrm_contact_sort_name', $row) && 
                 array_key_exists('civicrm_contact_id', $row) ) {
                if ( $value = $row['civicrm_contact_sort_name'] ) {
					$url = CRM_Utils_System::url( "civicrm/contact/view"	, 
												  'reset=1&cid=' . $row['civicrm_contact_id'],
												  $this->_absoluteUrl );
					$rows[$rowNum]['civicrm_contact_sort_name_link' ] = $url;
					$rows[$rowNum]['civicrm_contact_sort_name_hover'] =  
						ts("View Contact Summary for this Contact.");
                }
                $entryFound = true;
            }
            
            // convert participant ID to links
            if ( array_key_exists('civicrm_participant_participant_id', $row) && 
                 array_key_exists('civicrm_contact_id', $row) ) {
                if ( $value = $row['civicrm_participant_participant_id'] ) {
                         $url = CRM_Utils_System::url( "civicrm/contact/view/participant"	, 
                                                       'reset=1&id='.$row['civicrm_participant_participant_id'].'&cid='.$row['civicrm_contact_id'].'&action=view',
                                                       $this->_absoluteUrl );
                         $rows[$rowNum]['civicrm_participant_participant_id_link' ] = $url;
                         $rows[$rowNum]['civicrm_participant_participant_id_hover'] =  
                             ts("View Participant Record for this Contact.");
                }
                $entryFound = true;
            }
            
            // convert event name to links
            if ( array_key_exists('civicrm_participant_event_id', $row) ) {
                if ( $value = $row['civicrm_participant_event_id'] ) {
                    $rows[$rowNum]['civicrm_participant_event_id'] = 
                        CRM_Event_PseudoConstant::event( $value, false );
                    $url = CRM_Report_Utils_Report::getNextUrl( 'event/Income', 
                                                                'reset=1&force=1&event_id_op=eq&event_id_value='.$value,
                                                                $this->_absoluteUrl, $this->_id );
                    $rows[$rowNum]['civicrm_participant_event_id_link' ] = $url;
                    $rows[$rowNum]['civicrm_participant_event_id_hover'] = 
                        ts("View Event Income Details for this Event");
                }
                $entryFound = true;
            }
            
            // handle event type id
            if ( array_key_exists('civicrm_event_event_type_id', $row) ) {
                if ( $value = $row['civicrm_event_event_type_id'] ) {
                    $rows[$rowNum]['civicrm_event_event_type_id'] = $eventType[$value];
                }
                $entryFound = true;
            }
            
            // handle participant status id
            if ( array_key_exists('civicrm_participant_status_id', $row) ) {
                if ( $value = $row['civicrm_participant_status_id'] ) {
                    $rows[$rowNum]['civicrm_participant_status_id'] = 
                        CRM_Event_PseudoConstant::participantStatus( $value, false );
                }
                $entryFound = true;
            }
			
				// handle participant role id
            if ( array_key_exists('civicrm_participant_role_id', $row) ) {
                if ( $value = $row['civicrm_participant_role_id'] ) {
                    $roles = explode( CRM_Core_DAO::VALUE_SEPARATOR, $value ); 
                    $value = array( );
                    foreach( $roles as $role) {
                        $value[$role] = CRM_Event_PseudoConstant::participantRole( $role, false );
                    }
                    $rows[$rowNum]['civicrm_participant_role_id'] = implode( ', ', $value );
                }
                $entryFound = true;
            }
			
            
            // skip looking further in rows, if first row itself doesn't 
            // have the column we need
            if ( !$entryFound ) {
                break;
            }
        }
    }
}
