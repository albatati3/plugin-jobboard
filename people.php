<?php if ( ! defined('ABS_PATH')) exit('ABS_PATH is not loaded. Direct access is not allowed.');

    // only admin access
    if( !osc_is_admin_user_logged_in() ) osc_die('Admin access only');

    class JobboardPeople
    {
        public function __construct()
        {
            $feature = (int) osc_get_preference('new_feature_add_applicant', 'jobboard_plugin');
            if( $feature === 0 ) {
                osc_set_preference('new_feature_add_applicant', 1, 'jobboard_plugin');
            }
        }

        public function main()
        {
            if( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
                if( Params::getParam('add_new_applicant') ) {
                    $this->add_applicant();
                }
                if( Params::getParam('delete_applicant') ) {
                    $this->delete_applicant();
                }
            }

            $search = array();
            $search['conditions'] = $this->get_search_conditions();
            $search['order']      = $this->get_search_order();
            $search['limit']      = $this->get_search_limit();

            // get applicants info
            $people = ModelJB::newInstance()->search($search['limit']['offset'], $search['limit']['length'], $search['conditions'], $search['order']['col'], $search['order']['dir']);
            list($displayed, $total) = ModelJB::newInstance()->searchCount($search['conditions'], $search['order']['col'], $search['order']['dir']);

            // pagination
            $search['pagination'] = $this->get_search_pagination(count($people), $displayed, $total);

            // different status
            $aStatus   = jobboard_status();
            $aStatuses = $aStatus;
            $status    = array();
            if(count($aStatus) > 0) {
                foreach($aStatus as $aS) {
                    $status[$aS["id"]] = $aS["name"];
                }
            }

            // get notes and listing info
            for($i = 0; $i < count($people); $i++) {
                $people[$i]['notes']   = ModelJB::newInstance()->getNotesFromApplicant($people[$i]['pk_i_id']);
                $people[$i]['listing'] = ModelJB::newInstance()->getJobsAttrByItemId($people[$i]['fk_i_item_id']);
            }

            $urlOrder = osc_admin_base_url(true).'?'.$_SERVER['QUERY_STRING'];
            $urlOrder = preg_replace('/&iPage=(\d+)?/', '', $urlOrder) ;
            $urlOrder = preg_replace('/&sOrderCol=([^&]*)/', '', $urlOrder) ;
            $urlOrder = preg_replace('/&sOrderDir=([^&]*)/', '', $urlOrder) ;

            $mSearch = new Search();
            $mSearch->limit(0, 100);
            $aItems = $mSearch->doSearch();
            View::newInstance()->_exportVariableToView('items', $aItems);

            // navbar
            $navbar = $this->navbar();

            $statusID = 0;
            if(Params::getParam("viewUnread") && Params::getParam("viewUnread") == 1 ) {
                $statusID = '-1';
            } else if(Params::getParam("viewAll") && Params::getParam("viewAll") == 1 ) {
                $statusID = 'all';
            } else if(Params::getParam("statusId")) {
                $statusID = Params::getParam("statusId");
            }

            // load
            require_once(JOBBOARD_VIEWS . 'applicants/list.php');
        }

        /**
         *
         * @return array
         */
        private function get_search_conditions()
        {
            $conditions = array();
            if(Params::getParam('jobId')!='') {
                if(Params::getParam('jobId') > 0) {
                    $conditions['item'] = Params::getParam('jobId');
                } else if(Params::getParam('jobId') == -1) {
                     $conditions['spontaneous'] = 1;
                }
            }
            // default active status
            if(Params::getParam('statusId')=='') {
                Params::setParam('statusId', 0);
            }
            if(Params::getParam('statusId')>=0) {
                $conditions['status'] = Params::getParam('statusId');
            }
            if(Params::getParam('viewUnread')=='1') {
                $conditions['unread'] = 1;
                unset( $conditions['status'] );
            }
            if(Params::getParam('viewAll')=='1') {
                $conditions = null;
                unset($conditions['status'] );
            }
            if(Params::getParam('uncorrected_forms')!='') {
                $conditions['uncorrected_forms'] = Params::getParam('uncorrected_forms');
            }
            if(Params::getParam('sEmail')!='') {
                $conditions['email'] = Params::getParam('sEmail');
            }
            if(Params::getParam('sName')!='') {
                $conditions['name'] = Params::getParam('sName');
            }
            if(Params::getParam('sSex')!='') {
                $conditions['sex'] = Params::getParam('sSex');
            }
            if(Params::getParam('catId')!='') {
                $conditions['category'] = Params::getParam('catId');
            }
            // age
            if(Params::getParam('minAge')!='') {
                $conditions['minAge'] = Params::getParam('minAge');
            }
            if(Params::getParam('maxAge')!='') {
                $conditions['maxAge'] = Params::getParam('maxAge');
            }
            if(Params::getParam('rating')!='') {
                $conditions['rating'] = Params::getParam('rating');
            }

            return $conditions;
        }

        private function get_search_order()
        {
            $order = array(
                'col' => 'a.dt_date',
                'dir' => 'DESC'
            );

            // Get order from Params
            if( Params::getParam('sOrderCol') !== '' ) {
                $order['col'] = Params::getParam('sOrderCol');
            }
            // Get direction from Params
            if( Params::getParam('sOrderDir') !== '' ) {
                $order['dir'] = Params::getParam('sOrderDir');
            }

            return $order;
        }

        private function get_search_limit()
        {
            // default values
            $limit = array(
                'offset' => 0,
                'length' => 10
            );

            // get display length from params
            $iDisplayLength = Params::getParam('iDisplayLength');
            if( is_numeric($iDisplayLength) ) {
                $limit['length'] = $iDisplayLength;
            }

            // get page from params to calc offset
            $iPage = 1;
            if( is_numeric(Params::getParam('iPage')) && Params::getParam('iPage') > 1 ) {
                $iPage = Params::getParam('iPage');
            }

            $limit['offset'] = ($iPage - 1) * $limit['length'];

            return $limit;
        }

        private function get_search_pagination($showing, $displayed, $total)
        {
            $pagination = array(
                'page'      => 1,
                'length'    => 10,
                'displayed' => $displayed,
                'total'     => $total
            );

            // get display length from params
            $iDisplayLength = Params::getParam('iDisplayLength');
            if( is_numeric($iDisplayLength) ) {
                $pagination['length'] = $iDisplayLength;
            }

            // get page from params to calc offset
            $iPage = Params::getParam('iPage');
            if( is_numeric($iPage) && $iPage > 1 ) {
                $pagination['page'] = $iPage;
            }

            // calc from and to
            $pagination['from'] = (($pagination['page'] - 1) * $pagination['length']) + 1;
            $pagination['to']   = $pagination['page'] * $pagination['length'];
            if( $pagination['to'] > $pagination['displayed'] ) {
                $pagination['to'] = $pagination['displayed'];
            }

            return $pagination;
        }

        private function navbar() {
            $shortcuts = array();

            $shortcuts['all'] = array();
            $totalApplicantsShortcut = ModelJB::newInstance()->countApplicantsByStatus(false /*all*/);
            $shortcuts['all']["id"] = 'all';
            $shortcuts['all']['total'] = $totalApplicantsShortcut;
            $shortcuts['all']['url'] = osc_admin_render_plugin_url('jobboard/people.php') . '&viewAll=1';
            $shortcuts['all']['active'] = false;
            if( Params::getParam('viewAll') ) {
                $shortcuts['all']['active'] = true;
            }
            $shortcuts['all']['text'] = sprintf(__('All (%1$s)', 'jobboard'), $totalApplicantsShortcut);

            $shortcuts['unread'] = array();
            $totalApplicantsShortcut = ModelJB::newInstance()->countApplicantsUnread();
            $shortcuts['unread']["id"] = '-1';
            $shortcuts['unread']['total'] = $totalApplicantsShortcut;
            $shortcuts['unread']['url'] = osc_admin_render_plugin_url('jobboard/people.php') . '&viewUnread=1';
            $shortcuts['unread']['active'] = false;
            if( Params::getParam('viewUnread') ) {
                $shortcuts['unread']['active'] = true;
            }
            $shortcuts['unread']['text'] = sprintf(__('Unread (%1$s)', 'jobboard'), $totalApplicantsShortcut);

            $aStatuses = jobboard_status();
            foreach($aStatuses as $aStatus) {
                $statusName = strtolower($aStatus["name"]);
                $shortcuts[$statusName] = array();
                $totalApplicantsShortcut = ModelJB::newInstance()->countApplicantsByStatus($aStatus["id"]);
                $shortcuts[$statusName]["id"]     = $aStatus["id"];
                $shortcuts[$statusName]['total']  = $totalApplicantsShortcut;
                $shortcuts[$statusName]['url']    = osc_admin_render_plugin_url('jobboard/people.php') . '&statusId=' . $aStatus["id"] ;
                $shortcuts[$statusName]['active'] = false;
                if( Params::getParam('statusId') == '0' && !Params::getParam('viewUnread') && !Params::getParam('viewAll') ) {
                    $shortcuts[$statusName]['active'] = true;
                }
                $shortcuts[$statusName]['text'] = sprintf(__('%1$s (%2$s)', 'jobboard'), $aStatus["name"], $totalApplicantsShortcut);
            }

            return $shortcuts;
        }

        private function add_applicant()
        {
            $applName   = Params::getParam("applicant-name");
            $applEmail  = Params::getParam("applicant-email");
            $applPhone  = Params::getParam("applicant-phone");
            $applBday   = date("Y-m-d", strtotime(Params::getParam("applicant-birthday")));

            $applSex    = Params::getParam("applicant-sex");
            $applJob    = Params::getParam("applicant-job");
            $applStatus = Params::getParam("applicant-status");
            $applFile   = Params::getFiles("applicant-attachment");
            $applRating = Params::getParam("applicant-rating");

            //insert applicant
            ModelJB::newInstance()->insertApplicant($applJob, $applName, $applEmail, '', $applPhone, $applBday, $applSex);

            //get Applicant id
            $aApplicant = current(ModelJB::newInstance()->getLastApplicant());

            //set rating
            ModelJB::newInstance()->setRating($aApplicant["pk_i_id"], $applRating);

            //update status
            if($applStatus != '') {
                ModelJB::newInstance()->changeStatus($aApplicant["pk_i_id"], $applStatus);
            }

            //insert file in DB
            ModelJB::newInstance()->insertFile($aApplicant["pk_i_id"], $applFile["name"]);

            //insert file in disk
            $jobboardContact = new JobboardContact();
            $jobboardContact->uploadCV($applFile, $aApplicant["pk_i_id"]);
        }

        private function delete_applicant()
        {
            ModelJB::newInstance()->deleteApplicant(Params::getParam('id'));
        }
    }

    $jp = new JobboardPeople();
    $jp->main();

    // EOF