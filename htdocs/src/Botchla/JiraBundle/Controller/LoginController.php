<?php
namespace Botchla\JiraBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use Botchla\JiraBundle\Security\User\JiraUserProvider;
use Botchla\JiraBundle\Entity\Service\WebserviceService;


class LoginController extends Controller
{
    /**
     * @Route("/login", name="login")
     * @Template("BotchlaJiraBundle:Login:login.html.twig")
     */
    public function loginAction()
    {
        $request = $this->getRequest();
        $session = $request->getSession();

        // set default data
        $default_data = array(
            'jiralocation' => null,
            'last_username' => $session->get(SecurityContext::LAST_USERNAME),
        );

        // get post
        if ( count( $request->request->all() ) > 0 ) {
            $data = $request->request->all();
            try {
                $jiraUserProvider = new JiraUserProvider($data['_username'], $data['_password'], $data['jiralocation']);
            } catch (Exception $e) {
                print $e->getMessage();
            }

            if (isset($jiraUserProvider)) {
                $jiraUser = $jiraUserProvider->loadUserByUsername(null);

                // get token
                $token = new UsernamePasswordToken($jiraUser, null, 'JiraUserProvider', array('ROLE_USER'));
                // Set token in security handler
                $this->container->get('security.context')->setToken($token);
                // And in session
                $session->set('_security_secured_area',  serialize($token));
            }
        }


        // get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(
                SecurityContext::AUTHENTICATION_ERROR
            );
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }


        // return succes page if succesful
        if ($error == null && isset($jiraUser) ) {
            return $this->render(
                'BotchlaJiraBundle:Login:succes.html.twig'
            );
        }

        // return loginform
        return $this->render(
            'BotchlaJiraBundle:Login:login.html.twig',
            array(
                // last username entered by the user
                'last_username' => $default_data['last_username'],
                'error'         => $error,
                'jiralocation'  => $default_data['jiralocation'],
            )
        );
    }
}