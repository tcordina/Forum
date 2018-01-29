<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Message;
use AppBundle\Entity\Post;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PostController extends Controller
{
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $posts = $em->getRepository('AppBundle:Post')->findAll();

        return $this->render('post/index.html.twig', array(
            'posts' => $posts,
        ));
    }

    /**
     * @Security("has_role('ROLE_USER')")
     */
    public function newAction(Request $request)
    {
        $post = new Post();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $form = $this->createForm('AppBundle\Form\PostType', $post);
        $form->handleRequest($request);
        $post->setAutor($user);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($post);
            $em->flush();

            return $this->redirectToRoute('post_show', array('id' => $post->getId()));
        }

        return $this->render('post/new.html.twig', array(
            'post' => $post,
            'form' => $form->createView(),
        ));
    }

    public function showAction(Post $post, Request $request)
    {
        $deleteForm = $this->createDeleteForm($post);

        $em = $this->getDoctrine()->getManager();

        $message = new Message();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $message->setPost($post);
        $message->setAutor($user);
        $msgForm = $this->createForm('AppBundle\Form\MessageType', $message);
        $msgForm->handleRequest($request);

        if ($msgForm->isSubmitted() && $msgForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($message);
            $em->flush();

            return $this->redirectToRoute('post_show', array('id' => $post->getId()));
        }

        $listMessages = $em
            ->getRepository('AppBundle:Message')
            ->findBy(array('post' => $post))
        ;

        return $this->render('post/show.html.twig', array(
            'isAutor' => $user == $post->getAutor(),
            'post' => $post,
            'listMessages' => $listMessages,
            'delete_form' => $deleteForm->createView(),
            'message_form' => $msgForm->createView(),
        ));
    }

    public function editAction(Request $request, Post $post)
    {
        $author = $post->getAutor();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $admin = $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN');
        if($author == $user ||  $admin) {
            $deleteForm = $this->createDeleteForm($post);
            $editForm = $this->createForm('AppBundle\Form\PostType', $post);
            $editForm->handleRequest($request);

            if ($editForm->isSubmitted() && $editForm->isValid()) {
                $this->getDoctrine()->getManager()->flush();

                return $this->redirectToRoute('post_edit', array('id' => $post->getId()));
            }

            return $this->render('post/edit.html.twig', array(
                'post' => $post,
                'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
            ));
        }else {
            throw new NotFoundHttpException('Cette page n\'existe pas.');
        }
    }

    public function deleteAction(Request $request, Post $post)
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $admin = $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN');
        if($post->getAutor() == $user || $admin) {
            $form = $this->createDeleteForm($post);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->remove($post);
                $em->flush();
            }

            return $this->redirectToRoute('post_index');
        }else {
            throw new NotFoundHttpException('Cette page n\'existe pas.');
        }
    }

    private function createDeleteForm(Post $post)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('post_delete', array('id' => $post->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}