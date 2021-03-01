<?php

namespace Drupal\vactory_keycloak\Provider;


use pviojo\OAuth2\Client\Provider\KeycloakResourceOwner as BaseKeycloakResourceOwner;

class KeycloakResourceOwner extends BaseKeycloakResourceOwner
{
  /**
   * Raw response
   *
   * @var array
   */
  protected $response;

  /**
   * Email domain
   *
   * @var string
   */
  protected $emailDomain;

  /**
   * Creates new resource owner.
   *
   * @param array  $response
   */
  public function __construct(array $response = array(), $email_domain = '')
  {
    $this->response = $response;
    $this->emailDomain = $email_domain;
  }

  /**
   * Get resource owner id
   *
   * @return string|null
   */
  public function getId()
  {
    return $this->response['sub'] ?: null;
  }

  /**
   * Get resource owner email
   *
   * @return string|null
   */
  public function getEmail()
  {
    if (isset($this->response['email']) && !empty($this->response['preferred_username'])) {
      return $this->response['email'];
    }

    return md5($this->response['sub']) . '@' . $this->emailDomain;
  }

  /**
   * Get resource owner name
   *
   * @return string|null
   */
  public function getName()
  {
    if (isset($this->response['preferred_username']) && !empty($this->response['preferred_username'])) {
      return $this->response['preferred_username'];
    }
    elseif (isset($this->response['name']) && !empty($this->response['name'])) {
      return $this->response['name'];
    }

    return md5($this->response['sub']);
  }
  /**

  /**
   * Get resource owner roles for all clients
   *
   * @return array
   */
  public function getRoles()
  {
    return $this->response['roles'] ?: [];
  }

  /**
   * Get resource owner roles for given client
   *
   * @param string  $client
   *
   * @return array
   */
  public function getRolesForClient($client)
  {
    return isset($this->response['roles'][$client]['roles']) ? $this->response['roles'][$client]['roles'] : [];
  }

  /**
   * Check if resource owner has a given role for given client
   *
   * @param string  $client
   * @param string  $role
   *
   * @return boolean
   */
  public function hasRoleForClient($client, $role)
  {
    return in_array($role, $this->getRolesForClient($client));
  }

  /**
   * Check if resource owner has a access to a given client (have at least one role)
   *
   * @param string  $client
   *
   * @return boolean
   */
  public function hasAccessToClient($client)
  {
    return !empty($this->getRolesForClient($client));
  }

  /**
   * Return all of the owner details available as an array.
   *
   * @return array
   */
  public function toArray()
  {
    return $this->response;
  }
}
