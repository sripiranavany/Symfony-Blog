<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Form\CategoryType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends Controller
{
    /**
     * @Route("/category", name="category")
     */
    public function index(Request $request)
    {
        $articles = $this->getDoctrine()->getRepository(Category::class)->findAll();
        /**
         * @var $paginator \Knp\Component\Pager\Paginator
         */
        $paginator = $this->get('knp_paginator');
        $result = $paginator->paginate(
            $articles,
            $request->query->getInt('page',1),
            $request->query->getInt('limit',5)
        );
        $usr= $this->get('security.token_storage')->getToken()->getUser();
        // print_r($usr);die;
        if ($usr != "anon.") {
            $loginUser = $usr->getId();
        } else {
            $loginUser = null;
        }
        
        return $this->render('category/index.html.twig', [
            'controller_name' => 'CategoryController',
            'articles' => $result,
            'LoginUser' => $loginUser,
        ]);
    }

    /**
     * @Route("/add_category",name="AddCategory")
     */
    public function save(Request $request){
        $cat = new Category();
        $form = $this->createForm(CategoryType::class,$cat);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $usr= $this->get('security.token_storage')->getToken()->getUser();
            $cat->setUserId($usr);
            $em->persist($cat);
            $em->flush();

            return $this->redirectToRoute('category');
        }
        return $this->render('category/add.html.twig',[
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/category/view/{id}", name="viewCategory")
     */
    public function view(Request $request, $id){
        $articles = $this->getDoctrine()->getRepository(Article::class)->findBy(['category' => $id],['id' => 'ASC']);
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
     * @Route("/category/delete/{id}",name="deleteCategory")
     */
    public function delete(Request $request, $id){
        $cat = $this->getDoctrine()->getRepository(Category::class)->find($id);
        $art = $this->getDoctrine()->getRepository(Article::class)->findBy(['category' => $id]);
        $usr= $this->get('security.token_storage')->getToken()->getUser();
        if ($usr != "anon.") {
            if ($usr->getId() == $cat->getUserId()->getId()) {
                // Check the category is used
                if (count($art)) {
                    $this->addFlash('notice','This Category is in Use!! so You Cant Delete this!!!');
                    return $this->redirectToRoute('category');
                } else {
                    $em = $this->getDoctrine()->getManager();
                    $em->remove($cat);
                    $em->flush();
                    return $this->redirectToRoute('category');
                }
                
            } else{
                $this->addFlash('notice','You Cant delete this category!! you are not the owner of this category!!!');
                return $this->redirectToRoute('category');
            }
        } else {
            $this->addFlash('notice','Please Login!!!');
            return $this->redirectToRoute('category');
        }
    }
}
