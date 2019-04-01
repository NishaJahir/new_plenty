<?php
/**
 * This module is used for real time processing of
 * Novalnet payment module of customers.
 * Released under the GNU General Public License.
 * This free contribution made by request.
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * @author       Novalnet
 * @copyright(C) Novalnet. All rights reserved. <https://www.novalnet.de/>
 */

namespace Novalnet\Models;

use Plenty\Modules\Plugin\DataBase\Contracts\Model;

/**
 * Class TransactionLog
 *
 * @property int     $id
 * @property int     $orderNo
 * @property int     $amount
 * @property int     $callbackAmount
 * @property string  $referenceTid
 * @property string  $transactionDatetime
 * @property string  $tid
 * @property string  $paymentName
 */
class TransactionLog extends Model
{
    public $id;
    public $orderNo;
    public $amount;
    public $callbackAmount;
    public $referenceTid;
    public $transactionDatetime;
    public $tid;
    public $paymentName;

    /**
     * Returns table name to create during build
     *
     * @return string
     */
    public function getTableName(): string
    {
        return 'Novalnet::TransactionLog';
    }
}
