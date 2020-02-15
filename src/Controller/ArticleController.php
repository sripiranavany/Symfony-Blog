<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Form\ArticleType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends AbstractController
{
    /**
     * @Route("/article", name="app_home")
     */
    public function index()
    {        
        $articles = $this->getDoctrine()->getRepository(Article::class)->findAll();
        return $this->render('article/index.html.twig', [
            'articles' => $articles,
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
            $article->setUserId($usr->getId());
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
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/article/view/{id}", name="viewArticle")
     */
    public function view($id){
        $usr= $this->get('security.token_storage')->getToken()->getUser();
        // print_r($usr);die;
        if ($usr != "anon.") {
            $loginUser = $usr->getId();
        } else {
            $loginUser = null;
        }
        $article = $this->getDoctrine()->getRepository(Article::class)->find($id);
        return $this->render('article/view.html.twig',[
            'article' => $article,
            'LoginUser' => $loginUser
        ]);
    }

    /**
     * @Route("/article/delete/{id}", name="deleteArticle")
     */
    public function delete($id){
        $article = $this->getDoctrine()->getRepository(Article::class)->find($id);
        $postImage = 'uploads/article/'. $article->getPostImage();
        if (isset($postImage)) {
            unlink($postImage);
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($article);
        $em->flush();
        
        return $this->redirectToRoute("app_home");
    }
}

?>
