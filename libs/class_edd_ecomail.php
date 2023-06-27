<?php
if (!class_exists('Clvr_EDD_Ecomail')){

    class Clvr_EDD_Ecomail{
        const PAID = 'paid';
        const ORDER = 'order';
        protected $name = 'eddtoecomail';
        private $settings;
        private $client;
        private $ecomailLists;
        

        public function __construct()
        {
            require 'class_edd_ecomail_settings.php';
            $this->settings = new Clvr_EDD_Mautic_Settings();
            add_action( 'add_meta_boxes', array($this,'add_metabox') );
            add_filter( 'edd_metabox_fields_save', array($this,'save_metabox') );
            //add_shortcode('mautic_segment', array($this,'eddtomautic_segment_plus_shortcode'));
            //add_shortcode('mautic_asset', array($this,'eddtomautic_asset_plus_shortcode'));
            add_action( 'edd_complete_purchase', array($this,'send_after_complete_payment'));
            add_action( 'edd_insert_payment',  array($this,'send_after_payment'), 10, 2 );
            add_action('init', array($this,'debug'));
        }

        public function getName(){
            return $this->name;
        }

        public function isInitialized(){
            global $edd_options;
            $username = $edd_options['edd_ecomail_api'];
            return (!empty($username));
        }

        public function getClient(){
            if ($this->isInitialized()){
                if (!empty($this->client)){
                    return $this->client;
                }else
                {
                    global $edd_options;
                    $ecomail = new Ecomail($edd_options['edd_ecomail_api']);
                    $this->client = $ecomail;
                    return $this->client;
                }
            }else{
                return false;
            }
            
            
        }

        

        public function add_metabox() {
            $id = $this->getName();
            if ( current_user_can( 'edit_product', get_the_ID() ) ) {
                add_meta_box( 'edd_' . $id, 'Segmenty po objednání', array($this,'render_metabox') , 'download', 'side' );
                add_meta_box( 'edd_payment_' . $id, 'Segmenty po zaplacení', array($this,'render_payment_metabox') , 'download', 'side' );
                add_meta_box( 'edd_unsubscribe_' . $id, 'Odhlásit segmenty po objednání', array($this,'render_unsubscribe_metabox') , 'download', 'side' );
                add_meta_box( '_edd_payment_unsubscribe_' . $id, 'Odhlásit segmenty po zaplacení', array($this,'render_payment_unsubscribe_metabox') , 'download', 'side' );
            }
        }

        public function getLists(){
            if ($this->isInitialized()){
                // if (!empty($this->ecomailLists)){
                //     return $this->ecomailLists;
                // }
                try{
                    $unsanitized_lists = $this->getClient()->getListsCollection();
                }catch (Exception $e){
                    return [
                        -1 => 'Něco se děsně pokazilo. Prosím kontaktujte autora pluginu'
                    ];
                }
                
                $sanitized_list = array();
                if (is_array($unsanitized_lists)){
                    foreach ($unsanitized_lists as $list_item){
                        if (isset($list_item['id']) && isset($list_item['name'])){
                            $sanitized_list += [ $list_item['id'] => $list_item['name'] ];
                        }
                        
                    
                        //$sanitized_list[$list_item['id']] = $list_item['name'];

                    }
                    $sanitized_list[-1] = 'Nechci využívat segment pro tuto akci';
                    $this->ecomailLists = $sanitized_list;
                    return $sanitized_list;
                }else{
                    return [
                        -1 => 'Nezdařila se komunikace s ecomail'
                    ];
                }    

            }else{
                return [
                    -1 => 'Prosím zadejte API Klíč a uložte nastavení'
                ];
            }

            return [
                -1 => 'Něco se děsně pokazilo. Prosím kontaktujte autora pluginu'
            ];

            

        }

        

        public function renderSelect($name,$selected_list_id){
            echo '<select name="' .$name.'">';

            foreach( self::getLists() as $list_id => $list_name ) {
                    
                echo '<option value="'. esc_attr( $list_id ). '" ';
                if ($selected_list_id == $list_id){
                    echo 'selected="selected"';

                }

                echo '">' .$list_name . '</option>';
            }    
                    
           

            


            echo '</select>';
        }

        
        
        public function getSelectedList($post_id,$meta_name){
            $list = get_post_meta($post_id,$meta_name,true);
            if (empty($list)){
                $list = -1;
            }
            return $list;
        }

        

        public function render_metabox() {

            global $post;
            
            $id = $this->getName();
            $name = '_edd_' . esc_attr( $id );
            $list_id = $this->getSelectedList($post->ID,$name);
            

            echo '<p>' . __( 'Select the segments you wish buyers to be subscribed to when purchasing.', 'eddtomautic' ) . '</p>';
            echo '<label>';
            $this->renderSelect($name,$list_id);
            echo '</label><br/>';
        }

        public function render_payment_metabox() {

                global $post;
                
                $id = $this->getName();
                $name = '_edd_payment_' . esc_attr( $id );
                $list_id = $this->getSelectedList($post->ID,$name);

                echo '<p>' . __( 'Zapsat do segmentů po zaplacení', 'eddtomautic' ) . '</p>';
                echo '<label>';
                $this->renderSelect($name,$list_id);
                echo '</label><br/>';

            }

        public function render_unsubscribe_metabox(){

            global $post;
            

            $id = $this->getName();
            $name = '_edd_unsubscribe_'. esc_attr( $id );
            $list_id = $this->getSelectedList($post->ID,$name);
            echo '<p>' . __( 'Upon purchasing, the user will be removed from below selected segments:', 'eddtomautic' ) . '</p>';
            echo '<label>';
                $this->renderSelect($name,$list_id);
                echo '</label><br/>';
        }

        public function render_payment_unsubscribe_metabox(){

            global $post;
            
            $id = $this->getName();
            $name = '_edd_payment_unsubscribe_' . esc_attr( $id );
            $list_id = $this->getSelectedList($post->ID,$name);
            echo '<p>' . __( 'Upon purchasing, the user will be removed from below selected segments:', 'eddtomautic' ) . '</p>';
            echo '<label>';
                $this->renderSelect($name,$list_id);
                echo '</label><br/>';
            
        }

        public function save_metabox( $fields ) {


            $id = $this->getName();

            $fields[] = '_edd_' . esc_attr( $id );
            $fields[] = '_edd_payment_' . esc_attr( $id );
            $fields[] = '_edd_unsubscribe_' . esc_attr( $id );
            $fields[] = '_edd_payment_unsubscribe_' . esc_attr( $id );
            return $fields;
        }

        public function subscribe_user($user_data,$list_id){
            return $this->getClient()->addSubscriber($list_id,$user_data);
            
        }

        public function unsubscribe_user($user_email,$list_id){
            return $this->getClient()->removeSubscriber($list_id,$user_email);
        }

        

        public function eddtomautic_segment_plus_shortcode($atts = [], $content = null, $tag = ''){

            // normalize attribute keys, lowercase
            $atts = array_change_key_case((array)$atts, CASE_LOWER);
            // override default attributes with user attributes
            $wporg_atts = shortcode_atts([
                                             'id' => 1,
                                                                                 'count' => 0,
                                         ], $atts, $tag);
            $segmentApi = $this->getApiContext('leads');
            $allLists = $segmentApi->getLists();
            $segment_alias = $allLists[$wporg_atts['id']]['alias'];
            $search_filter = "segment:" .$segment_alias;
            $contactsApi = $this->getApiContext('contacts');
            $asset_download = $contactsApi->getList($search_filter,0,0);
            $super_total = $asset_download['total'] + $wporg_atts['count'];

            // start output
             $o = '';
            $o = $o .$super_total;
            // enclosing tags


             return $o;
        }

        public function eddtomautic_asset_plus_shortcode($atts = [], $content = null, $tag = ''){

            // normalize attribute keys, lowercase
            $atts = array_change_key_case((array)$atts, CASE_LOWER);
            // override default attributes with user attributes
            $wporg_atts = shortcode_atts([
                                             'id' => 1,
                                                                                 'count' => 0,
                                         ], $atts, $tag
                        );
            $assetApi = $this->getApiContext('assets');
            $aset = $assetApi->get($wporg_atts['id']);
            $asset_download = $aset['asset']['downloadCount'];
            $super_total = $asset_download + $wporg_atts['count'];
            // start output
            $o = '';
                $o = $o .$super_total;
                // enclosing tags


            return $o;
        }

        public function getUserData($payment_id){
            $user_info = edd_get_payment_meta_user_info( $payment_id );
            $payment      = new EDD_Payment( $payment_id );
            $payment_meta   = $payment->get_meta();
            $user_data = [
                'name' => isset($user_info['first_name']) ? $user_info['first_name'] : '',
                'surname'	=> isset($user_info['last_name']) ? $user_info['last_name'] : '',
                'email'		=> $user_info['email'],
                'phone' => get_post_meta($payment_id,'telefon',true),
                'street' => get_post_meta($payment_id,'street',true),
                'city' => get_post_meta($payment_id,'city', true)
            ];
            $ecomail_data =[
                'subscriber_data' => $user_data,
                'trigger_autoresponders' => true,
                'update_existing'=> true,
                'resubscribe'=> true
            ];

            return $ecomail_data;

        }

        public function send_after_complete_payment( $payment_id ){            
            $subscribe_list = $this->sanitize_lists($payment_id, false, true);
            $unsubscribe_list = $this->sanitize_lists($payment_id, true, true);
            $user_data = $this->getUserData($payment_id); 
            if (!empty($subscribe_list)){
                foreach ($subscribe_list as $list_key=>$subscribe_segment_id){
                    $expectation = $this->subscribe_user($user_data,$subscribe_segment_id);
                }    
            }   
            
           if(!empty($unsubscribe_list)){
             foreach($unsubscribe_list as $segment_key => $segment_id){
              $result_unsubscribe = $this->unsubscribe_user($user_data,$segment_id);
           }
        }

    }


    public function send_after_payment($payment_id){

        $subscribe_list = $this->sanitize_lists($payment_id, false);
        $unsubscribe_list = $this->sanitize_lists($payment_id, true);
        $user_data = $this->getUserData($payment_id);            
           

           if (!empty($subscribe_list)){
                foreach ($subscribe_list as $list_key=>$subscribe_segment_id){

                     $expectation = $this->subscribe_user($user_data,$subscribe_segment_id);

                }
            }    

           
           if(!empty($unsubscribe_list)){
             foreach($unsubscribe_list as $segment_key => $segment_id){
              $result_unsubscribe = $this->unsubscribe_user($user_data,$segment_id);
           }
        }

    }

    public function sanitize_lists($payment_id,$unsubscribe,$paid=false){

        global $edd_options;

       if ($unsubscribe){
             if($paid){
                 $first_part = '_edd_payment_unsubscribe_';
             }else {
                 $first_part = '_edd_unsubscribe_';
             }
       }else{
             if($paid){
                 $first_part = '_edd_payment_';
             }else {
                 $first_part = '_edd_';
             }

       }
       $meta = get_post_meta ($payment_id, '_edd_payment_meta', true);
         $downloads = $meta['downloads'];
         $download_ids = array();
         foreach ($downloads as $download){
             $download_ids[] = $download['id'];

         }
         $id = $this->getName();
         $desired_meta_field = $first_part . $id;
         foreach ($download_ids as $download_id){
             $lists[]     =get_post_meta( $download_id, $desired_meta_field, true );

         }
         if (isset($edd_options['edd_ecomail_default_segment'])){

            if ((!$edd_options['edd_ecomail_default_segment']) && (!$unsubscribe) && (!$paid)){
             $lists[] = $edd_options['edd_ecomail_list'];
            }
             if($paid && (!$unsubscribe) && (!$edd_options['edd_ecomail_default_segment'])){
                 $lists[] = $edd_options['edd_ecomail_purchase_list'];
             }
         }         
         $sanitized_list = array();
         $i = 0;
         foreach ($lists as $list_item){
             if (is_array($list_item)){
                 foreach($list_item as $key=>$value){
                     $sanitized_list[]=$value;
                 }
             }else{
                 $sanitized_list[]=$list_item;

             }
         }
         $sanitized_list = array_unique($sanitized_list);

        if (($key = array_search(-1, $sanitized_list)) !== false) {
            unset($sanitized_list[$key]);
        }


        
         return $sanitized_list;
     }

      public function debug()
     {
         if (isset($_GET['listener']) && $_GET['listener'] == 'ecomail'){
            if (isset($_GET['payment'])){
                $payment_id = $_GET['payment'];
            }
            else $payment_id = 44;
            $this->showSanitizedLists($payment_id);
            exit();
         }
     }

     public function showSanitizedLists($payment_id){
        $subscribe_list = $this->sanitize_lists($payment_id, false, true);
        $unsubscribe_list = $this->sanitize_lists($payment_id, true, true);
        $user_data = $this->getUserData($payment_id);  
        print_r('subscribe');
        print_r($subscribe_list);
        print_r('unsubscribe');
        print_r($unsubscribe_list);
        print_r('user data');
        print_r($user_data);
        print_r('subscribing');  
        foreach ($subscribe_list as $list_key=>$subscribe_segment_id){
                 $expectation = $this->subscribe_user($user_data,$subscribe_segment_id);
                 print_r($expectation);
        }

        if(!empty($unsubscribe_list)){
            print_r('unsubscribing');
             foreach($unsubscribe_list as $segment_key => $segment_id){
              $result_unsubscribe = $this->unsubscribe_user($user_data,$segment_id);
              print_r($result_unsubscribe);
           }

        }   
        
        

     }


    } //end class

} // end if
