<?php

if (!class_exists('Clvr_EDD_Ecomail_Settings')){

    class Clvr_EDD_Mautic_Settings extends Clvr_EDD_Ecomail{

        public function __construct()
        {
            add_filter( 'edd_settings_sections_extensions', array($this,'settings_section') );
            add_filter( 'edd_settings_extensions', array($this,'add_settings') );
            //add_action( 'edd_purchase_form_user_info_fields', array($this,'checkout_fields') );
            //add_filter( 'edd_payment_meta', array($this,'store_payment_meta') );
        }

        public function settings_section($sections){
            $sections[$this->getName().'-settings'] = __( 'Propojení EDD s Mauticem', $this->getName() );
            
			return $sections;
        }
        
        public function add_settings($settings){
            $local_settings = array(
                array(
                    'id' => 'edd_mautic_settings',
                    'name' => '<strong>' . __( 'Ecomail Export Settings', 'edd_mautic' ) . '</strong>',
                    'desc' => __( 'Configure the export settings', 'edd_mautic' ),
                    'type' => 'header'
                ),
                array(
                    'id' => 'edd_ecomail_api',
                    'name' =>  __( 'Api Klíč', 'edd_mautic' ) ,
                    'desc' => __( 'Správa účtu - Integrace - zkopírovaný API klíč', 'edd_mautic' ),
                    'type' => 'text',
                    'size' => 'regular'
        
                ),
                
                array(
                    'id'      => 'edd_ecomail_list',
                    'name'    => __( 'Výchozí Segment po objednání', 'edd_mautic'),
                    'desc'    => __( 'Select the segment you wish to subscribe buyers to', 'edd_mautic' ),
                    'type'    => 'select',
                    'options' => $this->getLists()
                ),
                array(
                 'id'      => 'edd_ecomail_purchase_list',
                 'name'    => __( 'Výchozí Segment po zaplacení', 'edd_mautic'),
                 'desc'    => __( 'Select the segment you wish to subscribe buyers to', 'edd_mautic' ),
                 'type'    => 'select',
                'options' => $this->getLists()
             ),
           array(
                   'id'       => 'edd_ecomail_default_segment',
                   'name'     => __( 'Nepoužívat výchozí segmenty', 'edd_mautic'),
                   'desc'     => __( 'If you select this, you will have to segment users per download', 'edd_mautic' ),
                   'type'    => 'checkbox',
                ),
            );
            if ( version_compare( EDD_VERSION, 2.5, '>=' ) ) {
				$local_settings = array( $this->getName().'-settings' => $local_settings );
			}
			return array_merge( $settings, $local_settings );
            
        }

        public function checkout_fields(){
            ?>
            <p id="edd-ecomail-pohlavi-wrap">
            <label class="edd-label" for="edd-pohlavi">Vaše pohlaví <span class="edd-required-indicator">*</span></label>
            <span class="edd-description">
            	Prosím zadejte své pohlaví
            </span>
            <select class="edd-input" id="edd-pohlavi" name="edd_pohlavi">
                <option value="male">Muž</option>
                <option value="female">Žena</option>
            </select>
        </p>
            <?php
        }

        public function all_extra_fields(){
            $eddvyfakturuj_fields[] = "edd_pohlavi";
            return $eddvyfakturuj_fields;
        }
        
        public function store_payment_meta($payment_meta){
            $extra_fields = $this->all_extra_fields();
            foreach ($extra_fields as $key => $extra_field){
                  if(empty($payment_meta[$extra_field])){
                      $payment_meta[$extra_field] = isset( $_POST[$extra_field] ) ? sanitize_text_field( $_POST[$extra_field] ) : '';
                  }
            }
            return $payment_meta;
          }

        

    }//endclass

}//endif