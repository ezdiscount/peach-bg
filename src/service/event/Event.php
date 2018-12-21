<?php
/**
 * Created by IntelliJ IDEA.
 * User: jibo
 * Date: 2018-12-07
 * Time: 10:48
 */

namespace service\event;

class Event
{
    const MAP = [
        'create_user' => 'CreateUser',
        'upgrade_vip' => 'UpgradeVip',
        'upgrade_partner' => 'UpgradePartner',
        'upgrade_super' => 'UpgradeSuper',
    ];
}
