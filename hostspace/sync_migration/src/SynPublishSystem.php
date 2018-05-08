<?php
/**
 * @file
 * Contains \Drupal\sync_migration\SynPublishSystem.
 */

namespace Drupal\sync_migration;

class SynPublishSystem{

  protected $base_url;

  public function __construct($domain_name) {
    $this->base_url = $domain_name;
  }

  public function synNewsData() {
    try {
      $uri = $this->base_url . '/syn/news_data_to_drupal.php';
      $client = \Drupal::httpClient();
      $response = $client->get($uri, array('headers' => array('Accept' => 'application/json')));
    } catch (\GuzzleHttp\Exception\RequestException $e) {
      echo 'Uh oh!' . $e->getMessage();
    }
    $json_str = (string)$response->getBody();
    $news_s = json_decode($json_str, true);

    foreach($news_s as $news) {
      $ctag = $news['cata_en_title'];

      $cata = taxonomy_term_load_multiple_by_name($ctag);
      $catalog = '';
      if(!empty($cata)) {
        foreach($cata as $key=>$v) {
          $catalog = $key;
        }
      }else {
        $term = entity_create('taxonomy_term', array(
          'name' => t($ctag),
          'parent' => array(0),
          'vid' => 'newsCategory',
        ));
        $term->save();
        $catalog = $term->id();
      }

      entity_create('node', array(
        'type' => 'news',
        'title' => $news['title'],
        'copyright' => 'HOSTSPACE',
        'views' => $news['views'],
        'summary' => $news['summary'],
        'body' => array('value' => $news['content'], 'format' => 'basic_html'),
        'status' => $news['status'],
        'copyrigh' => $news['copyrigh'],
        'uid' => \Drupal::currentUser()->id(),
        'category' => $catalog,
        'created' => $news['created']
      ))->save();
    }

    $config = \Drupal::configFactory()->getEditable('sync_migration.settings');
    $config->set('sync_news', 1);
    $config->save();
  }
  /**
   * 同步文章数据
   */
  public function synArticleData() {
   try {
      $uri = $this->base_url . '/syn/article_data_to_drupal.php';
      $client = \Drupal::httpClient(); 
      $response = $client->get($uri, array('headers' => array('Accept' => 'application/json')));
    } catch (\GuzzleHttp\Exception\RequestException $e) {
      echo 'Uh oh!' . $e->getMessage();
    }
    $json_str = (string)$response->getBody();
    $articles = json_decode($json_str, true);

    foreach($articles as $article) {
     // $ctag = $article['ctag'];
      $ctag = $article['cata_en_title'];
      $cata = taxonomy_term_load_multiple_by_name($ctag);
      $catalog = '';
      if(!empty($cata)) {
        foreach($cata as $key=>$v) {
          $catalog = $key;
        }
      }else {
        $term = entity_create('taxonomy_term', array(
          'name' => t($ctag),
          'parent' => array(0),
          'vid' => 'articleCategory',
        ));
        $term->save();
        $catalog = $term->id();
      }

      entity_create('node', array(
        'type' => 'articles',
        'title' => $article['title'],
        'copyright' => 'HOSTSPACE',
        'views' => $article['views'],
        'summary' => $article['summary'],
        'body' => array('value' => $article['content'], 'format' => 'basic_html'),
        'status' => $article['status'],
        'copyrigh' => $article['copyrigh'],
        'uid' => \Drupal::currentUser()->id(),
        'article_category' => $catalog,
        'created' => $article['created']
      ))->save();
    }
    $config = \Drupal::configFactory()->getEditable('sync_migration.settings');
    $config->set('sync_article', 1);
    $config->save();
  }
}
