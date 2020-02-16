<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Comment;
use App\Form\ArticleType;
use App\Form\CommentType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends Controller
{
    /**
     * @Route("/article", name="app_home")
     */
    public function index(Request $request)
    {        
        $articles = $this->getDoctrine()->getRepository(Article::class)->findAll();
        /**
         * @var $paginator \Knp\Component\Pager\Paginator
         */
        $paginator = $this->get('knp_paginator');
        $result = $paginator->paginate(
            $articles,
            $request->query->getInt('page',1),
            $request->query->getInt('limit',2)
        );
        return $this->render('article/index.html.twig', [
            'articles' => $result,
        ]);
    }

    /**
     * @Route("/add_article", name="addArticle")
     */
    public function save(Request $request){
        $categories = $this->getDoctrine()->getRepository(Category::class)->findAll();
        $article = new Article();
        $form = $this->createForm(ArticleType::class,$article,[
            'cate' => $categories
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $usr= $this->get('security.token_storage')->getToken()->getUser();
            $article->setUserId($usr);
            $article->setSlug($article->getTitle().'_'. uniqid());

            /** @var UploadedFile $postImage */
            $postImage = $form->get('post_image')->getData();
            if ($postImage) {
                $originalFilename = pathinfo($postImage->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = htmlentities($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$postImage->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $postImage->move(
                        $this->getParameter('article_image'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }
                $article->setPostImage($newFilename);
            }
            $em->persist($article);
            $em->flush();

            return $this->redirectToRoute("app_home");
        }
        return $this->render('article/add.html.twig',[
            'form' => $form->createView(),
            'article' => null
        ]);
    }

    /**
     * @Route("/article/view/{id}", name="viewArticle")
     */
    public function view(Request $request,$id){
        $usr= $this->get('security.token_storage')->getToken()->getUser();
        // print_r($usr);die;
        if ($usr != "anon.") {
            $loginUser = $usr->getId();
        } else {
            $loginUser = null;
        }
        $article = $this->getDoctrine()->getRepository(Article::class)->find($id);
        $comments = $this->getDoctrine()->getRepository(Comment::class)->findBy(['article' => $id],['id' => 'DESC']);
        $comment = new Comment();
        $form = $this->createForm(CommentType::class,$comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $usr= $this->get('security.token_storage')->getToken()->getUser();
            $comment->setUser($usr);
            $comment->setArticle($article);
            $comment->setName('Anomos');
            $em->persist($comment);
            $em->flush();

            return $this->redirect('/Symfony/blog/public/article/view/'.$id);
        }
        return $this->render('article/view.html.twig',[
            'article' => $article,
            'LoginUser' => $loginUser,
            'form' => $form->createView(),
            'comments' => $comments
        ]);
    }

    /**
     * @Route("/article/edit/{id}", name="editArticle")
     */
    public function edit(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $usr= $this->get('security.token_storage')->getToken()->getUser();
        $article = $this->getDoctrine()->getRepository(Article::class)->find($id);
        $postImage = 'uploads/article/'. $article->getPostImage();

        $categories = $this->getDoctrine()->getRepository(Category::class)->findAll();
        if (!$article) {
            throw $this->createNotFoundException('No Article found for id '. $id);
        }
        $form = $this->createForm(ArticleType::class,$article,[
            'cate' => $categories
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (isset($postImage)) {
                unlink($postImage);
                /** @var UploadedFile $postImage */
                $postImage = $form->get('post_image')->getData();
                if ($postImage) {
                    $originalFilename = pathinfo($postImage->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = htmlentities($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$postImage->guessExtension();

                    // Move the file to the directory where brochures are stored
                    try {
                        $postImage->move(
                            $this->getParameter('article_image'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        // ... handle exception if something happens during file upload
                    }
                    $article->setPostImage($newFilename);
                }
            }
            $em->flush();
            return $this->redirectToRoute("viewArticle",['id' => $id]);
        }
        if ($usr != "anon.") {
            if ($usr->getId() == $article->getUserId()->getId()) {
                return $this->render('article/add.html.twig',[
                    'form' => $form->createView(),
                    'article' => $article
                ]);
            }
        } else {
            return $this->redirectToRoute('viewArticle',['id' => $id]);
        }
        
    }

    /**
     * @Route("/article/delete/{id}", name="deleteArticle")
     */
    public function delete($id){
        $article = $this->getDoctrine()->getRepository(Article::class)->find($id);
        $usr= $this->get('security.token_storage')->getToken()->getUser();
        $postImage = 'uploads/article/'. $article->getPostImage();
        if ($usr != "anon.") {
            if ($usr->getId() == $article->getUserId()->getId()) {
                if (isset($postImage)) {
                    unlink($postImage);
                }
                $em = $this->getDoctrine()->getManager();
                $em->remove($article);
                $em->flush();
                
                return $this->redirectToRoute("app_home");
            }
        } else {
            return $this->redirectToRoute('viewArticle',['id' => $id]);
        }
    }
}

?>
