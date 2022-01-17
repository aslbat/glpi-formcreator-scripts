<?php
/*
After installing glpi formcreator plugin copy this file
in the glpi root and execute it via command line

This code creates a form for 10 devices to discard because are out-of-order
*/
require_once ('inc/includes.php');

// Allow executing via command line only, not via web
if (!defined('STDIN')) {
   die('Error: Unable to access this file directly');
}

// Check if formcreator plugin is active
if (!(new Plugin())->isActivated('formcreator')) {
   die('Formcreator plugin not activated');
}

$out_test = STDOUT; // log to standard out
//$out_test = fopen("/tmp/log-formcreator-script.txt", "a"); // log to file
//$out_test = null; // no log
fwrite($out_test, "Automatically creating out-of-order form.....\n\n");

// Number of device sections to create
$DEVICE_SECTIONS_NUMBER = 10;

// instanciate classes
$form           = new \PluginFormcreatorForm;
$form_section   = new \PluginFormcreatorSection;
$form_question  = new \PluginFormcreatorQuestion;

// create form
$form_id = $form->add([
   'name'                => "Out-of-order Report",
   'is_active'           => true
   //'validation_required' => \PluginFormcreatorForm_Validator::VALIDATION_USER
]);

#
# HEADER SECTION OUT-OF-ORDER REPORT
#
$header_section_id = $form_section->add([
   'name'                        => "Out-of-order Report",
   'plugin_formcreator_forms_id' => $form_id
]);

$template_question = [
   'name'                           => '--NAME--',
   'fieldtype'                      => 'text',
   'plugin_formcreator_sections_id' => false,
   'description'                    => '--DESC--',
   'required' => 1,
   'show_empty' => 0,
   'default_values'=>'',
   # without _parameters key the question cannot be created
   '_parameters' => ['text'=>['range'=>['range_min'=>'','range_max'=>''],'regex'=>['regex'=>'']]]
];

// date
$form_question->add(
   array_merge($template_question, [
      'name'                           => 'Report date',
      'fieldtype'                      => 'date',
      'plugin_formcreator_sections_id' => $header_section_id,
      'description'=> '&lt;p&gt;Report date&lt;/p&gt;'
   ])
);

// location
$form_question->add(
   array_merge($template_question, [
      'name'                           => 'Location',
      'fieldtype'                      => 'text',
      'plugin_formcreator_sections_id' => $header_section_id,
      'description'=> '&lt;p&gt;Devices to dismiss location&lt;/p&gt;'
   ])
);

// struttura
$form_question->add(
   array_merge($template_question, [
      'name'                           => 'Building',
      'fieldtype'                      => 'text',
      'plugin_formcreator_sections_id' => $header_section_id,
      'description'=> '&lt;p&gt;Devices to dismiss building&lt;/p&gt;'
   ])
);

#
# ADDING DEVICE SECTIONS
#
# Recording brand questions id, because i need the brand of the previous section
# to create the condition to show the next section
$qids_brand = [];

$device_fields = [
   ['name' => 'Brand',         'description' => 'device to dismiss brand'],
   ['name' => 'Model',         'description' => 'device to dismiss model'],
   ['name' => 'Serial num',    'description' => 'device to dismiss serial number'],
   ['name' => 'Inventory num', 'description' => 'device to dismiss inventory number']
];

for($i=1; $i <= $DEVICE_SECTIONS_NUMBER; $i++) {
   # The first device section is always visible, the other ones is visibile on the condition that the previous one is
   $show_rule = $i==1?\PluginFormcreatorCondition::SHOW_RULE_ALWAYS:\PluginFormcreatorCondition::SHOW_RULE_HIDDEN;
   
   $device_section_id = $form_section->add([
      'name'                        => "Device $i",
      'plugin_formcreator_forms_id' => $form_id,
      'show_rule' => $show_rule,
      // must add _conditions to prevent error 
      // Undefined index: _conditions in ...formcreator\inc\conditionnabletrait.class.php on line 89
      // because the conditions is added afterwards
      '_conditions' => false
   ]);

   fwrite($out_test, "Section ID - Device n. $i ::: $device_section_id\n");
   
   foreach ($device_fields as $device_field) {
      
      $question_id = $form_question->add([
         'name'                           => $device_field['name'],
         'fieldtype'                      => 'text',
         'plugin_formcreator_sections_id' => $device_section_id,
         'required' => 0,
         'show_empty' => 0,
         'default_values'=>'',
         'description'=> $device_field['description'],
         # without _parameters key the question cannot be created
         '_parameters' => ['text'=>['range'=>['range_min'=>'','range_max'=>''],'regex'=>['regex'=>'']]]
      ]);
      

      if ($device_field['name'] == 'Brand') {
         $qids_brand[] = $question_id;
      }
   } #end for fields

   if ($i>1) {
      // For the device sections after the first, here i insert the condition so that 
      // i show them only if the brand field of the previous section is not empty
      // Must be added the key 'show_rule' => \PluginFormcreatorCondition::SHOW_RULE_HIDDEN to the section
      // (see above), otherwise the rule is not visible

      // Get the question id of the previous section brand
      $question_id_previous_section_brand = $qids_brand[$i-2]; # -1 for the previous section -1 because sections start by 1 and qids by 0
      $condition        = new PluginFormcreatorCondition();
      $condition->add([
         'itemtype'                        => \PluginFormcreatorSection::class, //$section_device_obj->getType()
         'items_id'                        => $device_section_id,
         'plugin_formcreator_questions_id' => $question_id_previous_section_brand,
         'show_condition'                  => \PluginFormcreatorCondition::SHOW_CONDITION_NE,
         'show_value'                      => "",
         'show_logic'                      => \PluginFormcreatorCondition::SHOW_LOGIC_AND,
         'order'                           => 1,
      ]);
      
      // Log dei parametri
      fwrite($out_test, "Brand QIDs saved (for the condition): " . implode(', ', $qids_brand) . "\n");
      fwrite($out_test, "itemtype: " . \PluginFormcreatorSection::class . "\n");
      fwrite($out_test, "items_id: $device_section_id\n");
      fwrite($out_test, "plugin_formcreator_questions_id: " . $question_id_previous_section_brand . "\n");
   }
   
} #end for devices

fwrite($out_test, "FORMID: $form_id\n");
fclose($out_test);

echo("Form out-of-order report created with $DEVICE_SECTIONS_NUMBER device sections");
