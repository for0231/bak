<?php
namespace Drupal\online\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;


class OnlineContentForm extends ContentEntityForm {
  /**
   * (non-PHPdoc)
   * @see \Drupal\Core\Entity\EntityForm::getFormId()
   */
  public function getFormId() {
    return 'online_content_form';
  }
  /**
   * (non-PHPdoc)
   * @see \Drupal\Core\Entity\ContentEntityForm::form()
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    if(!$entity->isNew()){
      if(!$entity->get('status')->value) {
        $entity->set('status', 1);
        $entity->set('uid', \Drupal::currentUser()->id());
        $entity->save();
      }
      $id = $entity->get('id')->value;
    }
    $form['online'] = array(
      'theme' => 'online_list',
    );
    $form['id'] = array(
      '#title'=> 'ID',
      '#type' =>'textfield',
      '#weight' => 10,
      '#resizeable' => true,
      '#value' => $id,
      '#disabled' => true,
      '#attributes' => array(
        'class' => array('id'),
      ),
    );
    $form['id']['#disabled'] = true;
    $form['ask_name']['#disabled'] = true;
    $form['email']['#disabled'] = true;
    $form['content'] = array(
      '#title'=> 'content',
      '#type' =>'container',
      '#attributes' => array(
        'class' => array('return_content'),
      ),
      '#attached' => array(
        'library' => array('online/admin-online-content')
      )
    );

    $form['reply_content'] = array(
      '#title'=> '回复',
      '#type' =>'textfield',
      '#weight' => 16,
      '#resizeable' => true,
    );
    return $form;
  }
  public function save(array $form, FormStateInterface $form_state) {
  }
}
