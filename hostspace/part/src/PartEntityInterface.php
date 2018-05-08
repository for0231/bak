<?php

/**
 * @file
 * Contains \Drupal\part\PartEntityInterface.
 */

namespace Drupal\part;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a part entity.
 */
interface PartEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Set the brand for the parts.
   *
   * @param string $brand
   *   The brand which the part was added.
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setBrand($brand);

  /**
   * Returns the brand of the part.
   *
   * @return $string
   *  The brand of the part.
   */
  public function getBrand();

  /**
   * Set the model for the part.
   *
   * @param string $model
   *  The model which part was added.
   *
   * @return $this
   *  The class instance that this method is called on.
   */
  public function setModel($model);

  /**
   * Returns the model of the part.
   *
   * @return $string
   *  The model of the part.
   */
  public function getModel();

  /**
   * Set the standard for the part.
   *
   * @param string $standard
   *  The standard for the part.
   *
   * @return $this
   *  The class instance that this method is called on.
   */
  public function setStandard($standard);

  /**
   * Returns the standard of the part.
   *
   * @return $string
   *  The standard of the part.
   */
  public function getStandard();

 /**
   * Returns the stock of the part.
   *
   * @return $stock
   *  The stock of the stock.
   */
  public function getStock();

 /**
   * Returns the stock of the part.
   *
   * @return $stock
   *  The stock of the stock.
   */
  public function getUsedStock();
}
