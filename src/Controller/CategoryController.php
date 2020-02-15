<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{
    /**
     * @Route("/category", name="category")
     */
    public function index()
    {
        $articles = $this->getDoctrine()->getRepository(Category::class)->findAll();
        $usr= $this->get('security.token_storage')->getToken()->getUser();
        // print_r($usr);die;
        if ($usr != "anon.") {
            $loginUser = $usr->getId();
        } else {
            $loginUser = null;
        }
        
        return $this->render('category/index.html.twig', [
            'controller_name' => 'CategoryController',
            'articles' => $articles,
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
            $cat->setUserId($usr->getId());
            $em->persist($cat);
            $em->flush();

            return $this->redirectToRoute('category');
        }
        return $this->render('category/add.html.twig',[
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/category/delete/{id}",name="deleteCategory")
     */
    public function delete(Request $request, $id){
        $cat = $this->getDoctrine()->getRepository(Category::class)->find($id);
        $em = $this->getDoctrine()->getManager();
        $em->remove($cat);
        $em->flush();
        return $this->redirectToRoute('category');
    }
}
