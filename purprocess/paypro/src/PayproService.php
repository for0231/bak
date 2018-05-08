<?php

namespace Drupal\paypro;

use Drupal\Core\Database\Connection;

/**
 *
 */
class PayproService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  protected $condtions = [];

  /**
   *
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Paypro entity table save.
   *
   * @description ajax.paypre.pool.paypro.create
   */
  public function save($entity, $update = TRUE, $ids = []) {
    if ($update) {

    }
    else {
      try {
        $paypres = \Drupal::entityTypeManager()->getStorage('paypre')->loadMultiple($ids);

        $paypro_entity = \Drupal::entityTypeManager()->getStorage('paypro')->create([
          'fnos' => $ids,
        ]);
        $paypro_entity->save();

        $parts = $p = [];
        foreach ($paypres as $paypre) {
          // 更新付款单的状态为待支付.
          $paypre->set('status', 6)
            ->save();
          // 付款单包含的采购单.
          foreach ($paypre->get('cnos') as $purchase) {
            $pids = $purchase->entity->get('pids');
            // 采购单包含的配件.
            foreach ($pids as $row) {
              $parts[$row->entity->id()] = $row->entity;
              $p[$row->entity->id()] = $row->entity->id();
            }
          }
        }
        \Drupal::service('part.partservice')->setPno($paypro_entity, $parts);
      }
      catch (\Exception $e) {
        error_log(print_r('paypro service create Exception', 1));
      }
    }
  }

  /**
   * @description 创建付款单
   *  使用配件ids创建付款单
   */
  public function create($title, $ids) {
    $paypres = \Drupal::entityTypeManager()->getStorage('paypre')->loadMultiple($ids);

    $amount = 0;
    foreach ($paypres as $payre) {
      $amount += !is_null($payre->get('amount')->value) ? $payre->get('amount')->value : 0;
    }
    $paypro_entity = \Drupal::entityTypeManager()->getStorage('paypro')->create([
      'fnos' => $ids,
      'title' => $title,
      'amount' => $amount,
    ]);
    $paypro_entity->save();

    $parts = $p = [];
    foreach ($paypres as $paypre) {
      // 更新付款单的状态为待支付.
      $paypre->set('status', 6)
        ->save();
      // 付款单包含的采购单.
      foreach ($paypre->get('cnos') as $purchase) {
        $pids = $purchase->entity->get('pids');
        // 采购单包含的配件.
        foreach ($pids as $row) {
          $parts[$row->entity->id()] = $row->entity;
          $p[$row->entity->id()] = $row->entity->id();
        }
      }
    }
    \Drupal::service('part.partservice')->setPno($paypro_entity, $parts);
  }

  /**
   * @description 当支付审批被拒绝时。
   * @deprecated
   */
  public function updatePayproStatusRejectAction($paypro) {
    // @todo paypre的状态
    $paypres = $paypro->get('fno');
    $pay = [];
    foreach ($paypres as $row) {
      $pay[] = $row->entity;
    }
    // paypro里面只包含了一个paypre实体，.
    $paypre = current($pay);
    $paypre->set('status', 20)
      ->save();
  }

  /**
   * @description 获取付款单的最早时间或最晚时间
   * @param  $entity_array
   * @param 1 $status
   *   2:最晚.
   */
  public function getPayproCreated($entity_array, $status = 1) {
    $num = $i = 0;
    $old_entity = 0;
    foreach ($entity_array as $entity) {
      if ($i == 0) {
        $old_entity = $entity;
      }
      else {
        switch ($status) {
          case 1:
            // 取最小值.
            if ($entity->get('created')->value < $old_entity->get('created')->value) {
              $old_entity = $entity;
            }
            break;

          case 2:
            // 取最大值.
            if ($entity->get('created')->value > $old_entity->get('created')->value) {
              $old_entity = $entity;
            }
            break;
        }
      }

      $i++;
    }

    return empty($old_entity) ? 0 : date('Y-m-d H:i:s', $old_entity->get('created')->value);
  }

  /**
   * @description 获取付款单金额总和.
   */
  public function getPayproAmount($entity_paypros, $status = 0) {
    $amount = 0;
    foreach ($entity_paypros as $entity_paypro) {
      $amount += $this->getSinglePayproAmount($entity_paypro);
    }

    return $amount;
  }

  /**
   *
   */
  public function getSinglePayproAmount($entity_paypro) {
    $amount = 0;
    $fnos = $entity_paypro->get('fnos');
    foreach ($fnos as $fno) {
      $paypre_entity = $fno->entity;
      $amount += \Drupal::service('paypre.paypreservice')->getPaypresAmount($paypre_entity);
    }

    return $amount;
  }

  /**
   * @description 删除支付记录
   *
   * public function deletePayproPcnos($entity, $pcnos) {
   * $pcord_ids = $entity->get('pcnos');
   * foreach ($pcord_ids as $pcord_id) {
   * $pids[] = $pcord_id->entity->id();
   * }
   * $diff = array_diff($pids, $pcnos);
   * $entity->set('pcnos', $diff)
   * ->save();
   *
   * $storage = \Drupal::entityManager()->getStorage('pcord');
   * foreach ($pcnos as $pcno) {
   * $pay_record = $storage->load($pcno);
   * $pay_record->delete();
   * }
   * }
   */

  /**
   * @description 获取付款账号单币种的结构数据
   * @param $entities
   *   paypro entities
   */
  public function getSinglePayproFkTransform($paypro_entities) {
    $result = [];
    $amount = 0;
    foreach ($paypro_entities as $entity) {
      $result[$entity->get('fname')->value . ',' . $entity->get('fbank')->value . ',' . $entity->get('faccount')->value . ',' . $entity->get('ftype')->value][] = $entity;
    }

    return [$result, $amount];
  }

  /**
   * @description 计算已付、未付的金额值。
   * @param $status
   *   1: 已支付
   *        2: 未支付
   *        3: 所有
   */
  public function getPayproAmountByStatus($entity_paypros, $status = 0) {
    $amount = 0;

    foreach ($entity_paypros as $entity) {
      if (in_array($entity->get('status')->value, [8, 10]) && $status == 1) {
        $amount += $entity->get('amount')->value;
      }
      if (in_array($entity->get('status')->value, [0, 2, 6]) && $status == 2) {
        $amount += $entity->get('amount')->value;
      }
      if ($status == 3) {
        $amount += $entity->get('amount')->value;
      }
    }

    return $amount;
  }

  /**
   * @description 查找所有配件信息.
   * @param  $paypros
   */
  public function getPaypresByPayproId($paypros) {
    $parts = [];
    $ret = [];
    foreach ($paypros as $paypro) {
      $fnos = $paypro->get('fnos');
      foreach ($fnos as $fno) {
        $parts[$paypro->id()][$fno->entity->id()] = \Drupal::service('paypre.paypreservice')->getPartsInPaypre($fno->entity);
      }
    }
    return $parts;
  }

  /**
   *
   */
  public function timeLimit($begin, $end) {
    if ($begin) {
      $this->condtions[] = "created > $begin";
    }
    if ($end) {
      $this->condtions[] = "created < $end";
    }
  }

  /**
   *
   */
  public function timeLimit2($begin, $end) {
    if ($begin) {
      $this->condtions[] = "r.created > $begin";
    }
    if ($end) {
      $this->condtions[] = "r.created < $end";
    }
  }

  /**
   * 根据采购的对应的工单状态获取采购id.
   */
  public function getCgIds_pay($status) {
    $sql = "select id from purchase where status={$status}";
    $data = $this->database->query($sql)->fetchAll();
    return $data;
  }

  /**
   * @description 根据支付单实体获取付款单数据。
   */
  public function getPaypresByPaypro($paypro) {
    $fnos = $paypro->get('fnos');
    $paypre_entities = [];
    foreach ($fnos as $fno) {
      $paypre_entities[] = $fno->entity;
    }

    return $paypre_entities;
  }

  /**
   * @description 验证传入付款单数组里面的币种是否相同.
   * @param $paypres
   *   entity array.
   * @return 1 返回1时，不正常
   *   0 返回0时，正常
   */
  public function checkftypeforPaypresArray($paypres) {
    $ftype = [];
    foreach ($paypres as $paypre) {
      $ftype[] = $paypre->get('ftype')->target_id;
    }

    $unique_ftype = array_unique($ftype);
    return count($unique_ftype) > 1 ? 1 : 0;
  }

  /**
   * @description 验证传入付款单数组里面的收款账号是否相同.
   * @param $paypres
   *   entity array.
   * @return 1 返回1时，不正常
   *   0 返回0时，正常
   */
  public function checkacceptaccountforPaypresArray($paypres) {
    $acceptaccount = [];
    foreach ($paypres as $paypre) {
      $acceptaccount[] = $paypre->get('acceptaccount')->value;
    }

    $unique_acceptaccount = array_unique($acceptaccount);
    return count($unique_acceptaccount) > 1 ? 1 : 0;
  }

  /**
   * @description 计算当前支付单里面付款单的应付总金额
   */
  public function getCalAmountforPaypro($paypro) {
    $paypres = $this->getPaypresByPaypro($paypro);
    $amount = 0;
    foreach ($paypres as $paypre) {
      $amount += $paypre->get('amount')->value;
    }
    return $amount;
  }

  /**
   * 通过采购id获取所属的需求单id号.
   */
  public function getRno_id($cg_id) {
    $sql = "SELECT rno from part where cno={$cg_id} LIMIT 1,1";
    $data = $this->database->query($sql)->fetchAll();
    return $data;
  }

  /**
   * 未完全支付数据中未支付数据.
   */
  public function get_unpay_PartList($rno_list, $begin, $end) {
    $query = $this->database->select('part', 't');
    $query->fields('t');
    $query->condition('t.rno', $rno_list, 'IN');
    $query->condition('t.fno', 0);
    if (!empty($begin)) {
      $query->condition('t.created', $begin, '>=');
    }
    if (!empty($end)) {
      $query->condition('t.created', $end, '<=');
    }
    $data = $query->execute()->fetchAll();
    return $data;
  }

  /**
   * 已完全支付数据的所有详细数据.
   */
  public function get_pay_PartList($rno_list, $begin, $end) {
    $query = $this->database->select('part', 'p');
    $query->fields('p');
    $query->leftJoin('paypro', 'zf', 'p.pno = zf.id');
    $query->condition('p.rno', $rno_list, 'IN');
    if (!empty($begin)) {
      $query->condition('zf.changed', $begin, '>=');
    }
    if (!empty($end)) {
      $query->condition('zf.changed', $end, '<=');
    }
    $data = $query->execute()->fetchAll();
    return $data;
  }

  /**
   * @description 统计每月采购计划表
   */
  public function getPlanList($begin, $end) {
    $this->timeLimit2($begin, $end);
    $where = '';
    if (!empty($this->condtions)) {
      $where = "and " . implode(" and ", $this->condtions);
    }
    $sql = "SELECT * from requirement as r INNER JOIN part as p on r. id = p.rno where r.requiretype=1 $where";
    $data = $this->database->query($sql)->fetchAll();
    return $data;
  }

  /**
   * 通过物品名称获取以前的采购价格和币种.
   */
  public function get_price($name) {
    $sql = "SELECT unitprice,ftype from part where name='{$name}' AND unitprice >0  ORDER BY id LIMIT 1,1;";
    $data = $this->database->query($sql)->fetchAll();
    return $data;
  }

  /**
   * @description 重定义各种单据的编码的计数规则
   */
  public function getIkNumberCounterCode() {
    $config = \Drupal::configFactory()->getEditable('paypro.settings');

    $counter = empty($config->get('start')) ? 100 : $config->get('start');
    $next_counter = ++$counter;
    $config->set('start', $next_counter);
    $config->save();
    $formatter = empty($config->get('formatter')) ? 'Ymd' : $config->get('formatter');
    $new_no = date($formatter, time()) . $next_counter;
    return $new_no;
  }

}
