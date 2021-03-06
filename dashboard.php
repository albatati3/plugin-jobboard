<?php
    if(!osc_is_admin_user_logged_in()) {
        die;
    }

    $times_seen = (int) osc_get_preference('dashboard_tour_times_seen', 'jobboard_plugin');
    if( $times_seen < 3 ) {
        osc_set_preference('dashboard_tour_times_seen', $times_seen + 1, 'jobboard_plugin');
    }

    $status = jobboard_status();
    $mjb = ModelJB::newInstance();
?>
</div>
</div>
<div class="jobboard-dashboard">
    <?php osc_run_hook('jobboard_header_dashboard');?>
    <div class="grid-row grid-first-row grid-50">
        <div class="row-wrapper">
            <div class="widget-box">
                <div class="widget-box-title"><h3><?php _e('Activitiy', 'jobboard'); ?></h3></div>
                <div class="widget-box-content">
                    <table cellpadding="0" cellspacing="0" id="activity-stat">
                        <tbody>
                            <tr>
                                <td>
                                    <a href="<?php echo osc_admin_base_url(true); ?>?page=items" style="text-decoration:none;">
                                        <div class="card card-vacancies">
                                            <div class="container">
                                                <div class="icon-car"></div>
                                                <span><?php _e('Vacancies','jobboard'); ?></span>
                                                <?php
                                                $mSearch = new Search(true);
                                                $mSearch->addItemConditions(DB_TABLE_PREFIX.'t_item.b_enabled = 1');
                                                ?>
                                            </div>
                                            <b><?php echo $mSearch->count(); ?></b>
                                        </div>
                                    </a>
                                </td>
                                <td class="separate-cl">&nbsp;</td>
                                <td>
                                    <a href="<?php echo osc_admin_render_plugin_url("jobboard/people.php"); ?>" style="text-decoration:none;">
                                        <div class="card card-applicants">
                                            <div class="container">
                                                <div class="icon-car"></div>
                                                <span><?php _e('Applicants','jobboard'); ?></span>
                                                <?php
                                                list($iTotalDisplayRecords, $iTotalRecords) = ModelJB::newInstance()->searchCount();
                                                ?>
                                            </div>
                                            <b><?php echo $iTotalRecords; ?></b>
                                        </div>
                                    </a>
                                </td>
                                <td class="separate-cl">&nbsp;</td>
                                <td>
                                    <a href="<?php echo osc_admin_base_url(true); ?>?page=items" style="text-decoration:none;">
                                        <div class="card card-views">
                                            <div class="container">
                                                <div class="icon-car"></div>
                                                <span><?php _e('Total views','jobboard'); ?></span>
                                                <?php $allViews = ItemStats::newInstance()->getAllViews(); ?>
                                            </div>
                                            <b><?php echo $allViews; ?></b>
                                        </div>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <?php   $mSearch4 = new Search(true);
                                        $mSearch4->addTable(DB_TABLE_PREFIX."t_item_stats");
                                        $mSearch4->addField("SUM(".DB_TABLE_PREFIX."t_item_stats.i_num_views) as i_num_views");
                                        $mSearch4->addConditions(DB_TABLE_PREFIX."t_item_stats.fk_i_item_id = ".DB_TABLE_PREFIX."t_item.pk_i_id");
                                        $mSearch4->order('i_num_views');
                                        $mSearch4->set_rpp(1);
                                        $mSearch4->addGroupBy("fk_i_item_id");
                                        $mostViewedJob = $mSearch4->doSearch();?>
                                <td colspan="5"><div class="most-viwed"><span><?php _e('Most viewed', 'jobboard'); ?> - <b><?php printf(_n('%1$d view', '%1$d views', $mostViewedJob[0]['i_num_views'], 'jobboard'), $mostViewedJob[0]['i_num_views']); ?></b></span><a href="<?php echo osc_item_admin_edit_url($mostViewedJob[0]['fk_i_item_id']); ?>"><?php echo osc_highlight($mostViewedJob[0]['s_title'],30); ?></a></div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="grid-row grid-first-row grid-50">
        <div class="row-wrapper">
            <div class="widget-box">
                <div class="widget-box-title"><h3><?php _e('Recent activity', 'jobboard'); ?></h3></div>
                <div class="widget-box-content applicants_list_wdg">
                    <table class="table" cellpadding="0" cellspacing="0" id="applicants_list">
                        <tbody>
                            <?php $aActivity = ModelLogJB::newInstance()->getActivity(25);
                            $i = 0;
                            foreach($aActivity as $log){ ?>
                            <tr <?php if($i == 0){ echo 'class="table-first-row"'; } ?>>
                                <td><?php echo $log['s_data'];?><br/><span class='ago'><?php echo _jobboard_time_elapsed_string(strtotime($log['dt_date']), true) ?></span></td>
                            </tr>
                            <?php
                            $i++;
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="grid-row grid-first-row grid-100">
        <div class="row-wrapper">
            <div class="widget-box">
                <div class="widget-box-title"><h3 class="has-tabs"><?php _e('Recent applicants', 'jobboard'); ?></h3>
                    <ul class="tabs">
                        <?php foreach($status as $k => $v) {
                            echo '<li><a href="#status-'.$k.'">'.$v.'</a></li>';
                        }
                        ?>
                    </ul>
                </div>
                <div class="widget-box-content">
                    <?php foreach($status as $k => $v) {
                        echo '<div id="status-'.$k.'">';
                        echo '<table class="table" cellpadding="0" cellspacing="0"><tbody>';
                        echo '<thead><th>'.__('Applicant','jobboard').'</th><th>'.__('Job title','jobboard').'</th><th>'.__('Received','jobboard').'</th></thead>';
                        $people = ModelJB::newInstance()->search(0, 6, array('status'=>$k), 'a.dt_date', 'DESC');
                            if(count($people)){
                                foreach($people as $applicant){
                                    $item = Item::newInstance()->findByPrimaryKey($applicant['fk_i_item_id']);
                                    //Notes
                                    $notes = ModelJB::newInstance()->getNotesFromApplicant($applicant['pk_i_id']);
                                    $note_tooltip = '';
                                    for($i = 0; $i < count($notes); $i++) {
                                        $note_tooltip .= sprintf('<strong>%1$s</strong> - %2$s', date('d/m/Y H:i', strtotime($notes[$i]['dt_date'])), $notes[$i]['s_text']);
                                        if( $i < (count($notes) - 1) ) {
                                            $note_tooltip .= '<br/>';
                                        }
                                    }
                                    echo '<tr>';
                                    echo '<td><a href="'.osc_admin_render_plugin_url("jobboard/people_detail.php").'&people='.$applicant['pk_i_id'].'">'.$applicant['s_name']; if($applicant['b_has_notes'] == 1 ) { echo '<span class="note" data-tooltip="'.$note_tooltip.'"></span>'; } echo '</a></td>';
                                    if( !is_null(@$applicant['fk_i_item_id']) ) {
                                        echo '<td>'.osc_highlight(@$item['s_title'],30).'</td>';
                                    } else {
                                        echo '<td>'.__('Spontaneous application', 'jobboard').'</td>';
                                    }
                                    echo '<td>'._jobboard_time_elapsed_string( strtotime(@$applicant['dt_date']), true ) .'</td>';
                                    echo '</tr>';
                                }
                            }
                        echo '</tbody></table>';
                        echo '<p class="view-all"><a href="'.osc_admin_render_plugin_url("jobboard/people.php").'&iStatus='.$k.'">'.__('View all','jobboard').' '.$v.'</a></p>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
    <div class="grid-row grid-first-row grid-100">
        <div class="row-wrapper">