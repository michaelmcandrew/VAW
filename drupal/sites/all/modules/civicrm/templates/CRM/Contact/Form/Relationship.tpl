{*
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
*}
{* this template is used for adding/editing/viewing relationships  *}
{if $cdType }
  {include file="CRM/Custom/Form/CustomData.tpl"}
{else}
  {if $action eq 4 } {* action = view *}
      <h3>{ts}View Relationship{/ts}</h3>
        <div class="crm-block crm-content-block crm-relationship-view-block">
        <table class="crm-info-panel">
	    {foreach from=$viewRelationship item="row"}
            <tr>
                <td class="label">{$row.relation}</td> 
                <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.cid`"}">{$row.name}</a></td>
            </tr>
            {if $isCurrentEmployer}
                <tr><td class="label">{ts}Current Employee?{/ts}</td><td>{ts}Yes{/ts}</td></tr>
            {/if}
            {if $row.start_date}
                <tr><td class="label">{ts}Start Date{/ts}</td><td>{$row.start_date|crmDate}</td></tr>
            {/if}
            {if $row.end_date}
                <tr><td class="label">{ts}End Date{/ts}</td><td>{$row.end_date|crmDate}</td></tr>
            {/if}
            {if $row.description}
                <tr><td class="label">{ts}Description{/ts}</td><td>{$row.description}</td></tr>
            {/if}
	        {foreach from=$viewNote item="rec"}
		    {if $rec }
			    <tr><td class="label">{ts}Note{/ts}</td><td>{$rec}</td></tr>	
	   	    {/if}
            {/foreach}
            {if $row.is_permission_a_b}
                {if $row.rtype EQ 'a_b' AND $is_contact_id_a}
                     <tr><td class="label">&nbsp;</td><td><strong>'{$displayName}'</strong> can view and update information for <strong>'{$row.display_name}'</strong></td></tr>
                {else}
                     <tr><td class="label">&nbsp;</td><td><strong>'{$row.display_name}'</strong> can view and update information for <strong>'{$displayName}'</strong></td></tr>
                {/if}
            {/if}
            {if $row.is_permission_b_a}
                 {if $row.rtype EQ 'a_b' AND $is_contact_id_a}   
                     <tr><td class="label">&nbsp;</td><td><strong>'{$row.display_name}'</strong> can view and update information for <strong>'{$displayName}'</strong></td></tr>
                 {else}
                     <tr><td class="label">&nbsp;</td><td><strong>'{$displayName}'</strong> can view and update information for <strong>'{$row.display_name}'</strong></td></tr>
                 {/if}   
            {/if}
           
            <tr><td class="label">{ts}Status{/ts}</td><td>{if $row.is_active}{ts}Enabled{/ts} {else} {ts}Disabled{/ts}{/if}</td></tr>
        {/foreach}
        </table>
        {include file="CRM/Custom/Page/CustomDataView.tpl"}
        <div class="crm-submit-buttons"><input type="button" name='cancel' value="{ts}Done{/ts}" onclick="location.href='{crmURL p='civicrm/contact/view' q='action=browse&selectedChild=rel'}';"/></div>
        </div>
  {/if}

  {if $action eq 2 | $action eq 1} {* add and update actions *}
    <h3>{if $action eq 1}{ts}New Relationship{/ts}{else}{ts}Edit Relationship{/ts}{/if}</h3>
    <div class="crm-block crm-form-block crm-relationship-form-block">
            {if $action eq 1}
                <div class="description">
                {ts}Select the relationship type. Then locate target contact(s) for this relationship by entering a complete or partial name and clicking 'Search'.{/ts}
                </div>
            {/if}
            <table class="form-layout-compressed">
             <tr class="crm-relationship-form-block-relationship_type_id">
               <td class="label">{$form.relationship_type_id.label}</td><td>{$form.relationship_type_id.html}</td>
            {if $action EQ 2} {* action = update *}
                {literal}
                <script type="text/javascript">
                    var relType = 0;
                    cj( function( ) {
                        var relationshipType = cj('#relationship_type_id'); 
                        relationshipType.change( function( ) { 
                            changeCustomData( 'Relationship' );
                            currentEmployer( ); 
                        });
                        setPermissionStatus( relationshipType.val( ) ); 
                    });
                </script>
                {/literal} 
                <td><label>{$sort_name_b}</label></td></tr>
                <tr class="crm-relationship-form-block-is_current_employer">
                  <td class="label">
                     <span id="employee"><label>{ts}Current Employee?{/ts}</label></span>
                     <span id="employer"><label>{ts}Current Employer?{/ts}</label></span>
                  </td>
                  <td id="current_employer">{$form.is_current_employer.html}</td>
                </tr>
             </table>  
            {else} {* action = add *}
             </tr>
             <tr class="crm-relationship-form-block-rel_contact">
               <td class="label">{$form.rel_contact.label}</td>
                {literal}
                  <script type="text/javascript">
                    var relType = 0;
                    cj( function( ) {
                        createRelation( );
                        var relationshipType = cj('#relationship_type_id'); 
                        relationshipType.change( function() { 
                            cj('#relationship-refresh-save').hide();
			     cj('#saveButtons').hide();
                            cj('#rel_contact').val('');
                            cj("input[name='rel_contact_id']").val('');
                            createRelation( );
                            changeCustomData( 'Relationship' );
                            setPermissionStatus( cj(this).val( ) ); 
                        });
                        setPermissionStatus( relationshipType.val( ) ); 
                    });
                    
                    function createRelation(  ) {
                        var relType    = cj('#relationship_type_id').val( );
                        var relContact = cj('#rel_contact');
                        if ( relType ) {
                             relContact.unbind( 'click' );
                             cj("input[name='rel_contact_id']").val('');
                             var dataUrl = {/literal}'{crmURL p="civicrm/ajax/rest" h=0 q="className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&context=relationship&rel="}'{literal} + relType;
                             relContact.autocomplete( dataUrl, { width : 180, selectFirst : false, matchContains: true });
                             relContact.result(function( event, data ) {
                               	cj("input[name='rel_contact_id']").val(data[1]);
                                cj('#relationship-refresh-save').show( );
                                buildRelationFields( relType );
                             });
                        } else { 
                            relContact.unautocomplete( );
                            cj("input[name='rel_contact_id']").val('');
                            relContact.click( function() { alert( '{/literal}{ts}Please select a relationship type first.{/ts}{literal} ...' );});
                        }
                    }       
				  </script>
                {/literal}
                <td>{$form.rel_contact.html}</td>
              </tr>
              </table>
                <div class="crm-submit-buttons">
                    <span id="relationship-refresh" class="crm-button crm-button-type-refresh crm-button_qf_Relationship_refresh">{$form._qf_Relationship_refresh.html}</span>
                    <span id="relationship-refresh-save" class="crm-button crm-button-type-save crm-button_qf_Relationship_refresh_save" style="display:none">{$form._qf_Relationship_refresh_save.html}</span>
                    <span class="crm-button crm-button-type-cancel crm-button_qf_Relationship_cancel">{$form._qf_Relationship_cancel.html}</span>
                </div>
                <div class="clear"></div>

              {if $searchDone } {* Search button clicked *} 
                {if $searchCount || $callAjax}
                    {if $searchRows || $callAjax} {* we got rows to display *}
                        <fieldset id="searchResult"><legend>{ts}Mark Target Contact(s) for this Relationship{/ts}</legend>
                        <div class="description">
                            {ts}Mark the target contact(s) for this relationship if it appears below. Otherwise you may modify the search name above and click Search again.{/ts}
                        </div>
                        {strip}
			            {if $callAjax}
                			<div id="count_selected"> </div><br />
                			{$form.store_contacts.html}
		
                			{if $isEmployeeOf || $isEmployerOf}
                			     {$form.store_employer.html}     	
                			{/if}
			                {include file="CRM/common/jsortable.tpl"  sourceUrl=$sourceUrl useAjax=1 callBack=1 }
			            {/if}

                        <table id="rel-contacts" class="pagerDisplay">
			                <thead>
                                <tr class="columnheader">
                                    <th id="nosort" class="contact_select">&nbsp;</th>
                                    <th>{ts}Name{/ts}</th>
                                {if $isEmployeeOf}<th id="nosort" class="current_employer">{ts}Current Employer?{/ts}</th> 
                                {elseif $isEmployerOf}<th id="nosort" class="current_employer">{ts}Current Employee?{/ts}</th>{/if}
                                    <th>{ts}City{/ts}</th>
                                    <th>{ts}State{/ts}</th>
                                    <th>{ts}Email{/ts}</th>
                                    <th>{ts}Phone{/ts}</th>
                                </tr>
			                </thead>
 			                <tbody>
                			{if !$callAjax}
                                {foreach from=$searchRows item=row}
                                <tr class="{cycle values="odd-row,even-row"}">
                                    <td class="contact_select">{$form.contact_check[$row.id].html}</td>
                                    <td>{$row.type} {$row.name}</td>
                                    {if $isEmployeeOf}<td>{$form.employee_of[$row.id].html}</td>
                                    {elseif $isEmployerOf}<td>{$form.employer_of[$row.id].html}</td>{/if}
                                    <td>{$row.city}</td>
                                    <td>{$row.state}</td>
                                    <td>{$row.email}</td>
                                    <td>{$row.phone}</td>
                                </tr>
                                {/foreach}
                			{else}
                			    <tr><td colspan="5" class="dataTables_empty">Loading data from server</td></tr>
                			{/if}
                			</tbody>
                        </table>
                        {/strip}
                        </fieldset>
                        <div class="spacer"></div>
                    {else} {* too many results - we display only 50 *}
                        {if $duplicateRelationship}  
                            {capture assign=infoMessage}{ts}Duplicate relationship.{/ts}{/capture}
                        {else}   
                            {capture assign=infoMessage}{ts}Too many matching results. Please narrow your search by entering a more complete target contact name.{/ts}{/capture}
                        {/if}  
                        {include file="CRM/common/info.tpl"}
                    {/if}
                {else} {* no valid matches for name + contact_type *}
                        {capture assign=infoMessage}{ts}No matching results for{/ts} <ul><li>{ts 1=$form.rel_contact.value}Name like: %1{/ts}</li><li>{ts}Contact Type{/ts}: {$contact_type_display}</li></ul>{ts}Check your spelling, or try fewer letters for the target contact name.{/ts}{/capture}
                        {include file="CRM/common/info.tpl"}                
                {/if} {* end if searchCount *}
              {else}
              {/if} {* end if searchDone *}
        {/if} {* end action = add *}
        
            <div id = 'saveElements'>
                {if $action EQ 1}
                <div id='addCurrentEmployer'>
                   <table class="form-layout-compressed">  
                       <tr class="crm-relationship-form-block-add_current_employer">
                         <td class="label">{$form.add_current_employer.label}</td>
                         <td>{$form.add_current_employer.html}</td>
                       </tr>
                   </table> 
                </div>
                <div id='addCurrentEmployee'>
                   <table class="form-layout-compressed">   
                       <tr class="crm-relationship-form-block-add_current_employee">
                         <td class="label">{$form.add_current_employee.label}</td>
                         <td>{$form.add_current_employee.html}</td>
                       </tr>
                   </table>
                </div> 
                {/if}
                <table class="form-layout-compressed">
                    <tr class="crm-relationship-form-block-start_date">
                        <td class="label">{$form.start_date.label}</td>
                        <td>{include file="CRM/common/jcalendar.tpl" elementName=start_date}</td></tr>
                    <tr class="crm-relationship-form-block-end_date">
                        <td class="label">{$form.end_date.label}</td>
                        <td>{include file="CRM/common/jcalendar.tpl" elementName=end_date}<br />
                        <span class="description">{ts}If this relationship has start and/or end dates, specify them here.{/ts}</span></td>
                    </tr>
                    <tr class="crm-relationship-form-block-description">
                        <td class="label">{$form.description.label}</td>
                        <td>{$form.description.html}</td>
                    </tr>
                    <tr class="crm-relationship-form-block-note">
                        <td class="label">{$form.note.label}</td>
                        <td>{$form.note.html}</td>
                    </tr>
                    <tr class="crm-relationship-form-block-is_permission_a_b">
                        <td class="label"></td><td>{$form.is_permission_a_b.html}
                        <span id='permision_a_b-a_b' class="hiddenElement">
                            {if $action eq 1}
                                <strong>'{$sort_name_a}'</strong> {ts}can view and update information for selected contact(s){/ts}
                            {else}
                                <strong>'{$sort_name_a}'</strong> {ts}can view and update information for {/ts} <strong>'{$sort_name_b}'</strong>
                            {/if}
                        </span>
                        <span id ='permision_a_b-b_a' class="hiddenElement">
                            {if $action eq 1}
                                <strong>{ts}Selected contact(s)</strong> can view and update information for {/ts} <strong>'{$sort_name_a}'</strong>
                            {else}
                                <strong>'{$sort_name_b}'</strong> {ts}can view and update information for {/ts} <strong>'{$sort_name_a}'</strong>
                            {/if}
                        </span>
                        </td>
                    </tr>
                    <tr class="crm-relationship-form-block-is_permission_b_a">
                        <td class="label"></td><td>{$form.is_permission_b_a.html}
                        <span id='permision_b_a-b_a' class="hiddenElement">
                            {if $action eq 1}
                                <strong>'{$sort_name_a}'</strong> {ts}can view and update information for selected contact(s){/ts}
                            {else}
                                <strong>'{$sort_name_a}'</strong> {ts}can view and update information for {/ts} <strong>'{$sort_name_b}'</strong>
                            {/if}
                        </span>
                        <span id ='permision_b_a-a_b' class="hiddenElement">
                            {if $action eq 1}
                                <strong>{ts}Selected contact(s)</strong> can view and update information for {/ts} <strong>'{$sort_name_a}'</strong>
                            {else}
                                <strong>'{$sort_name_b}'</strong> {ts}can view and update information for {/ts} <strong>'{$sort_name_a}'</strong>
                            {/if}
                        </span>
                        </td>
                    </tr>
                    <tr class="crm-relationship-form-block-is_active">
                        <td class="label">{$form.is_active.label}</td>
                        <td>{$form.is_active.html}</td>
                    </tr>
                </table>
                {literal}
                    <script type="text/javascript">
                        function setPermissionStatus( relTypeDirection ) {
                            var direction = relTypeDirection.split( '_' );
                            cj('#permision_a_b-' + direction[1] + '_' + direction[2] ).show( );
                            cj('#permision_a_b-' + direction[2] + '_' + direction[1] ).hide( );
                            cj('#permision_b_a-' + direction[1] + '_' + direction[2] ).show( );
                            cj('#permision_b_a-' + direction[2] + '_' + direction[1] ).hide( );                            
                        }
                    </script>
                {/literal}
            </div>{* end of save element div *}
        <div id="customData"></div>
        <div class="spacer"></div>
        <div class="crm-submit-buttons" id="saveButtons"> {include file="CRM/common/formButtons.tpl" location="top"}</div> 
        {if $action EQ 1}
            <div class="crm-submit-buttons" id="saveDetails">
            <span class="crm-button crm-button-type-save crm-button_qf_Relationship_refresh_savedetails">{$form._qf_Relationship_refresh_savedetails.html}</span>
            <span class="crm-button crm-button-type-cancel crm-button_qf_Relationship_cancel">{$form._qf_Relationship_cancel.html}</span>
            </div>
        {/if}
      </div> {* close main block div *}
  {/if}
 
  {if $action eq 8}
     <fieldset><legend>{ts}Delete Relationship{/ts}</legend>
        <div class="status">
            {capture assign=relationshipsString}{$currentRelationships.$id.relation}{ $disableRelationships.$id.relation} {$currentRelationships.$id.name}{ $disableRelationships.$id.name }{/capture}
            {ts 1=$relationshipsString}Are you sure you want to delete the Relationship '%1'?{/ts}
        </div>
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
    </fieldset>	
  {/if}
{/if} {* close of custom data else*}

{if $callAjax}
{literal}
<script type="text/javascript">
var contact_checked  = new Array();
var employer_checked = new Array();
var employer_holdelement = new Array();
var countSelected = useEmployer = isRadio = 0;

{/literal} {if $isEmployeeOf || $isEmployerOf} {literal}
var storeElement  = 'store_employers';
var employerClass = 'current_employer';
useEmployer = 1;
{/literal} {/if} {if $isEmployeeOf} {literal}
isRadio = 1;
{/literal} {/if} {literal}

cj( function( ) {
    // clear old data if any
    cj('#store_contacts').val('');
    if ( useEmployer ) {
        cj('#store_employers').val('');
    } 

    cj('.pagerDisplay tbody tr .contact_select input').live('click', function () {
        var valueSelected = cj(this).val();	  
        if ( cj(this).attr('checked') == true ) {   
            contact_checked[valueSelected] =  valueSelected;
            countSelected++;
        } else if( contact_checked[valueSelected] ) {
            delete contact_checked[valueSelected];
            countSelected--;
            if ( useEmployer && employer_holdelement[valueSelected] ) {
                cj( employer_holdelement[valueSelected] ).attr('checked',false);
                delete employer_checked[valueSelected];
                delete employer_holdelement[valueSelected];
            } 
        }
        cj('#count_selected').html(countSelected +' Contacts selected.')  
    } );

    if ( useEmployer ) {
        cj('.pagerDisplay tbody tr .'+ employerClass +' input').live('click', function () {
            var valueSelected = cj(this).val();	
            if ( isRadio ) {
                employer_checked = new Array();
            }
            if ( cj(this).attr('checked') == true ) {
                // add validation to match with selected contacts
                if( !contact_checked[valueSelected] ) {
                    alert('Current employer / Current employee should be among the selected contacts.');
                    cj(this).attr('checked',false); 
                } else {
                    employer_checked[valueSelected] = valueSelected;
                    employer_holdelement[valueSelected] = this;
                }

            } else if ( employer_checked[valueSelected] ) {
                delete employer_checked[valueSelected]; 
                delete employer_holdelement[valueSelected];
            }
        } );
    }

});

function checkSelected( ) {
    cj('.pagerDisplay tbody tr .contact_select input').each( function( ) {
        if ( contact_checked[cj(this).val()] ) { 
            cj(this).attr('checked',true);
        }
    });

    if ( useEmployer ) {
        // register new elements
        employer_holdelement = new Array();
        cj('.pagerDisplay tbody tr .'+ employerClass +' input').each( function( ) {
            if ( employer_checked[cj(this).val()] ) { 
                cj(this).attr('checked',true);
                employer_holdelement[cj(this).val()] = this;
            }
        });  
    }	  	  
}

function submitAjaxData() {
    cj('#store_contacts').val( contact_checked.toString() );
    if ( useEmployer )  {
        cj('#store_employers').val( employer_checked.toString() ); 
    }
    return true;	 
}

</script>
{/literal}
{/if}

{if ($action EQ 1) OR ($action EQ 2) }
{*include custom data js file*}
{include file="CRM/common/customData.tpl"}
{literal}
<script type="text/javascript">

{/literal} {if $searchRows} {literal}
cj(".contact_select .form-checkbox").each( function( ) {
    if (this) { 
        cj(this).attr('checked',true);
    } 
});
{/literal} {/if} {literal}

{/literal} {if $action EQ 1}{literal} 
cj('#saveDetails').hide( );
cj('#addCurrentEmployer').hide( );
cj('#addCurrentEmployee').hide( );

cj(document).ready(function(){
  if ( cj.browser.msie ) {
       cj('#rel_contact').keyup( function(e) {
         if( e.keyCode == 9 || e.keyCode == 13 ) {
	     return false;
	     }
         cj("input[name='rel_contact_id']").val('');
         cj('#relationship-refresh').show( );
         cj('#relationship-refresh-save').hide( );
    }); } else {
         cj('#rel_contact').focus( function() {
         cj("input[name='rel_contact_id']").val('');
         cj('#relationship-refresh').show( );
         cj('#relationship-refresh-save').hide( ); 
}); }
});

{/literal}{if $searchRows || $callAjax}{literal} 
show('saveElements');
show('saveButtons');
{/literal}{else}{literal}
hide('saveElements');
hide('saveButtons');
{/literal}{/if}{/if}{literal}	

cj( function( ) {
    var relType = cj('#relationship_type_id').val( );
    if ( relType ) {
        var relTypeId = relType.split("_");
        if (relTypeId) {
            buildCustomData( 'Relationship', relTypeId[0]);
        }
    } else {
        buildCustomData('Relationship');
    }
});

function buildRelationFields( relType ) {
    {/literal} {if $action EQ 1} {literal} 
    if ( relType ) {
        var relTypeId = relType.split("_");
        if ( relTypeId[0] == 4 ) {
            if ( relTypeId[1] == 'a' ) {
                show('addCurrentEmployee');
                hide('addCurrentEmployer');
            } else {
                hide('addCurrentEmployee');
                show('addCurrentEmployer');
            }
        } else {
            hide('addCurrentEmployee');
            hide('addCurrentEmployer');
        }
        hide('relationship-refresh');
        show('relationship-refresh-save');
        show('details-save');
        show('saveElements');
        show('saveDetails');
        {/literal}{if $searchRows || $callAjax}{literal}
        hide('searchResult');
        {/literal}{/if}{literal}
        hide('saveButtons');
    } 
    {/literal}{/if}{literal} 	 
}

function changeCustomData( cType ) {
    {/literal}{if $action EQ 1} {literal}
    cj('#customData').html('');
    show('relationship-refresh');
    hide('saveElements');
    hide('addCurrentEmployee');
    hide('addCurrentEmployer');
    hide('saveDetails');
    {/literal}{if $searchRows || $callAjax}{literal}
    hide('searchResult');
    {/literal}{/if}{literal}
    {/literal}{/if} {literal}

    var relType = cj('#relationship_type_id').val( );
    if ( relType ) {
        var relTypeId = relType.split("_");
        if (relTypeId) {
            buildCustomData( cType, relTypeId[0]);
        }
    } else {
        buildCustomData( cType );
    }
}

</script>
{/literal}
{/if}
{if $action EQ 2}
{literal}
<script type="text/javascript">
   currentEmployer( );
   function currentEmployer( ) 
   {
      var relType = document.getElementById('relationship_type_id').value;
      if ( relType == '4_a_b' ) {
           show('current_employer', 'block');
           show('employee', 'block');
           hide('employer', 'block');
      } else if ( relType == '4_b_a' ) {
	   show('current_employer', 'block');
           show('employer', 'block');
           hide('employee', 'block');
      } else {
           hide('employer', 'block');
           hide('employee', 'block');
	   hide('current_employer', 'block');
      }
   }
</script>
{/literal}
{/if}
