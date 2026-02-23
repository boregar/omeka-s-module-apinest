<?php
namespace APINest\Form;

use Laminas\Form\Form;
use Laminas\Validator\Callback;

class ConfigForm extends Form {
  public function init() {

    $this->add([
      'type' => 'multicheckbox',
      'name' => 'apinest_allowed_apis',
      'options' => [
        'label' => 'Allowed APIs', // @translate
        'info' => 'Only requests sent through these APIs will be processed. Leave unchecked to disable the processing.', // @translate
        'use_hidden_element' => true,
        'checked_value' => 'yes',
        'unchecked_value' => 'no',
        'value_options' => [
          'REST' => 'REST API',
          'PHP' => 'PHP API',
        ],
      ],
      'attributes' => [
        'id' => 'apinest-allowed-apis',
      ],
    ]);

    $this->add([
      'type' => 'radio',
      'name' => 'apinest_default_merge_key',
      'options' => [
        'label' => 'Default merge key', // @translate
        'info' => 'If "Linked data" is checked, the data will be added to the JSON-LD tree under its corresponding key. If you choose "apinest", the data will be added to the "o:apinest" key.', // @translate
        'value_options' => [
          '1' => 'Linked data', // @translate
          '0' => 'o:apinest', // @translate
        ],
      ],
      'attributes' => [
        'id' => 'apinest-default-merge-key',
      ],
    ]);

  }
}
