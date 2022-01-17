<?php
/*
Dopo aver installato il plugin glpi formcreator copiare questo file
nella root di glpi ed eseguirlo.

Il codice crea un modulo con 10 apparecchiature da dismettere
*/
require_once ('inc/includes.php');

// Non consento l'esecuzione via web, ma solo da riga di comando
if (!defined('STDIN')) {
   die("Errore: Non &egrave; possibile accedere a questo file direttamente");
}

// Controllo se il plugin formcreator e' attivo
if (!(new Plugin())->isActivated('formcreator')) {
   die("Plugin formcreator non attivo");
}

$out_test = STDOUT; // su standard out
//$out_test = fopen("/tmp/NICOLAAAAAAAAAAA.txt", "a"); // su file
//$out_test = null; // nessun log
fwrite($out_test, "Creazione automatica modulo verbale fuori uso.....\n\n");

$NUMERO_SEZIONI_APPARECCHIATURA = 10;

// instanciate classes
$form           = new \PluginFormcreatorForm;
$form_section   = new \PluginFormcreatorSection;
$form_question  = new \PluginFormcreatorQuestion;

// create form
$form_id = $form->add([
   'name'                => "Verbale Fuori Uso",
   'is_active'           => true
   //'validation_required' => \PluginFormcreatorForm_Validator::VALIDATION_USER
]);

$section_verbale_id = $form_section->add([
   'name'                        => "Verbale fuori uso",
   'plugin_formcreator_forms_id' => $form_id
]);

# Tengo traccia delle questions Marca, perche' mi serve la Marca della sezione precedente
# per la condizione di visualizzazione della sezione successiva
$qids_marca = [];

$campi_apparecchiatura = [
   ['nome' => 'Marca',          'descrizione' => 'marca apparecchiatura da dismettere'],
   ['nome' => 'Modello',        'descrizione' => 'modello apparecchiatura da dismettere'],
   ['nome' => 'Matricola',      'descrizione' => 'matricola apparecchiatura da dismettere'],
   ['nome' => 'Inventario ASL', 'descrizione' => 'n. inventario apparecchiatura da dismettere']
];

for($i=1; $i <= $NUMERO_SEZIONI_APPARECCHIATURA; $i++) {
   # La prima la visualizzo sempre, le altre in base alla condizione
   $show_rule = $i==1?\PluginFormcreatorCondition::SHOW_RULE_ALWAYS:\PluginFormcreatorCondition::SHOW_RULE_HIDDEN;
   
   $section_apparecchiatura_id = $form_section->add([
      'name'                        => "Apparecchiatura $i",
      'plugin_formcreator_forms_id' => $form_id,
      'show_rule' => $show_rule,
      // must add _conditions to prevent error 
      // Undefined index: _conditions in ...formcreator\inc\conditionnabletrait.class.php on line 89
      // because the conditions is added afterwards
      '_conditions' => false
   ]);

   fwrite($out_test, "Section ID Apparecchiatura n. $i ::: $section_apparecchiatura_id\n");
   
   foreach ($campi_apparecchiatura as $campo_apparecchiatura) {
      
      $question_id = $form_question->add([
         'name'                           => $campo_apparecchiatura['nome'],
         'fieldtype'                      => 'text',
         'plugin_formcreator_sections_id' => $section_apparecchiatura_id,
         'required' => 0,
         'show_empty' => 0,
         'default_values'=>'',
         'description'=> $campo_apparecchiatura['descrizione'],
         # senza _parameters mi da errore nel creare la domanda e non me la crea
         '_parameters'     =>
            ['text' => [ 
               'range' => [ 'range_min' => '', 'range_max' => '' ],
               'regex' => [ 'regex' => '' ]
            ]]
      ]);
      

      if ($campo_apparecchiatura['nome'] == 'Marca') {
         $qids_marca[] = $question_id;
      }
   } #end for campi

   if ($i>1) {
      // Per le sezioni Apparecchiatura dopo la 1 metto la condizione in modo tale che le
      // visualizzo solo se il campo Marca della sezione precedente e' valorizzato
      // Va aggiunta la 'show_rule' => \PluginFormcreatorCondition::SHOW_RULE_HIDDEN alla sezione (vedi sopra)
      // altrimenti la regola non si vede
      
      // Mi recupero l'id della question Marca della sezione precedente
      $question_id_marca_sezione_precedente = $qids_marca[$i-2]; # -1 per la sezione precedente -1 perche' le sezioni partono da 1 e i qids da 0
      $condition        = new PluginFormcreatorCondition();
      $condition->add([
         'itemtype'                        => \PluginFormcreatorSection::class, //$section_apparecchiatura_obj->getType()
         'items_id'                        => $section_apparecchiatura_id, //vedere se vuole id o uuid
         'plugin_formcreator_questions_id' => $question_id_marca_sezione_precedente,
         'show_condition'                  => \PluginFormcreatorCondition::SHOW_CONDITION_NE,
         'show_value'                      => "",
         'show_logic'                      => \PluginFormcreatorCondition::SHOW_LOGIC_AND,
         'order'                           => 1,
      ]);
      
      // Log dei parametri
      fwrite($out_test, "QIDs Marca salvati (per condizione): " . implode(', ', $qids_marca) . "\n");
      fwrite($out_test, "itemtype: " . \PluginFormcreatorSection::class . "\n");
      fwrite($out_test, "items_id: $section_apparecchiatura_id\n");
      fwrite($out_test, "plugin_formcreator_questions_id: " . $question_id_marca_sezione_precedente . "\n");
   }
   
} #end for apparecchiatura

fwrite($out_test, "FORMID: $form_id\n");
fclose($out_test);

echo("Modulo Verbale fuori uso creato con $NUMERO_SEZIONI_APPARECCHIATURA sezioni apparecchiatura");
