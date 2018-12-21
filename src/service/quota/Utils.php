<?php

namespace service\quota;

use db\Mysql;
use db\SqlMapper;
use service\config\QuotaRate;
use service\UserPlan;

class Utils
{
    static function calc($id)
    {
        $rate = new QuotaRate();
        $quota = new SqlMapper('quota');
        $quota->load("id=$id");
        if (!$quota->dry()) {
            if ($quota['status'] != 0) {
                $buyerId = $quota['buyer'];
                $budget = $quota['budget'];

                // 自购奖
                $quota['buyer_earning'] = $quota['buyer_plan'] ? 0 : intval(floor($budget * $rate->get(QuotaRate::BUYER)));
                $taken = $quota['buyer_earning'];

                $chain = new SqlMapper('user_chain');

                // 分销奖
                $data = $chain->find("user=$buyerId and (length=1 or length=2)", ['order' => 'length', 'limit' => 2]);
                if (isset($data[0])) {
                    $father = $data[0];
                    $quota['father'] = $father['mentor'];
                    $quota['father_plan'] = $father['mentor_plan'];
                    $quota['father_earning'] = intval(floor($budget * $rate->get(QuotaRate::FATHER)));
                    $taken += $quota['father_earning'];
                }
                if (isset($data[1])) {
                    $grandpa = $data[1];
                    $quota['grandpa'] = $grandpa['mentor'];
                    $quota['grandpa_plan'] = $grandpa['mentor_plan'];
                    $quota['grandpa_earning'] = intval(floor($budget * $rate->get(QuotaRate::GRANDPA)));
                    $taken += $quota['grandpa_earning'];
                }

                // 团队奖
                $data = $chain->find(['user=? and mentor_plan>?', $buyerId, UserPlan::VIP], ['order' => 'length', 'limit' => 2]);
                $teamRate = $rate->get(QuotaRate::CHAIN_SUPER);
                if (isset($data[0])) {
                    $chainFather = $data[0];
                    $quota['chain_father'] = $chainFather['mentor'];
                    $quota['chain_father_plan'] = $chainFather['mentor_plan'];
                    switch ($quota['chain_father_plan']) {
                        case UserPlan::SUPER:
                            $quota['chain_father_earning'] = intval(floor($budget * $rate->get(QuotaRate::CHAIN_SUPER)));
                            $teamRate -= $rate->get(QuotaRate::CHAIN_SUPER);
                            break;
                        case UserPlan::PARTNER:
                            $quota['chain_father_earning'] = intval(floor($budget * $rate->get(QuotaRate::CHAIN_PARTNER)));
                            $teamRate -= $rate->get(QuotaRate::CHAIN_PARTNER);
                            break;
                        default:
                            // never access here
                            logging('error: unexpected chain plan');
                    }
                    $taken += $quota['chain_father_earning'];
                }
                if (isset($data[1])) {
                    $chainGrandpa = $data[1];
                    $quota['chain_grandpa'] = $chainGrandpa['mentor'];
                    $quota['chain_grandpa_plan'] = $chainGrandpa['mentor_plan'];
                    $quota['chain_grandpa_earning'] = intval(floor($budget * $teamRate));
                    $taken += $quota['chain_grandpa_earning'];
                }

                // 平级奖
                $db = Mysql::instance()->get();
                $plan = UserPlan::SUPER;
                $data = $db->exec("select b.mentor from user_chain a, user_chain b where a.user=$buyerId and a.mentor_plan=$plan and a.mentor=b.user and b.mentor_plan=$plan order by a.length, b.length limit 1");
                if ($data) {
                    $super = $data[0]['mentor'];
                    $quota['super'] = $super;
                    $quota['super_earning'] = intval(floor($budget * $rate->get(QuotaRate::SUPER)));
                    $taken += $quota['super_earning'];
                }

                $quota['margin'] = $budget - $taken;
                $quota['status'] = 1;
                $quota->save();

                logging("quota $id calc completed, budget: $budget, taken: $taken");
            } else {
                logging("quota $id not in calc status");
            }
        } else {
            logging("quota $id not found");
        }
    }
}
