<?php


namespace flexycms\FlexySecurityBundle\Controller;

use flexycms\FlexySecurityBundle\Entity\User;
use flexycms\FlexySecurityBundle\Form\UserType;
use flexycms\FlexySecurityBundle\Repository\UserRepository;
use flexycms\BreadcrumbsBundle\Utils\Breadcrumbs;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use flexycms\FlexyAdminFrameBundle\Controller\AdminBaseController;


class AdminUserController extends AdminBaseController
{

    private $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @Route("/admin/user", name="admin_user")
     */
    public function index(){

        $users = $this->getDoctrine()->getRepository(User::class)->findAll();

        $forRender = parent::renderDefault();
        $forRender['title'] = 'Пользователи';

        $forRender['users'] = $users;

        $breadcrumbs = new Breadcrumbs();
        $breadcrumbs->prepend($this->generateUrl("admin_user"), 'Пользователи');
        $breadcrumbs->prepend($this->generateUrl("admin_home"), 'Главная');
        $forRender['breadcrumbs'] = $breadcrumbs;


        $forRender['ajax'] = $this->generateUrl("admin_users_json");



        return $this->render('@FlexySecurity/index.html.twig', $forRender);
    }


    /**
     * @Route("/admin/users.json", name="admin_users_json")
     */
    public function listJSON(Request $request)
    {
        $draw = $request->get("draw");

        $start = $request->get("start");
        $length = $request->get("length");

        if ($length == -1) $length = 10;

        $search = $request->get("search");
        $order = $request->get("order");

        $searchValue = '';
        if (isset($search['value'])) {
            $searchValue = $search['value'];
        }

        // Определяем по какому полю сортировать
        $orderColumn = null;
        $orderDirection  = 'ASC';
        if (isset($order[0]) && isset($order[0]['column'])) $orderColumn = $order[0]['column'];
        if (isset($order[0]) && isset($order[0]['dir']) && $order[0]['dir'] == 'desc') $orderDirection = 'DESC';


        // Находим, сколько всего у нас рубрик
        $allItemsCount = $this->repository->countAll();

        // Находим, сколько рубрик попадают под фильтр без ограничения по страницам
        $itemsCount = $this->repository->countBySearch($searchValue);


        // Находим всех пользователей
        $orderColumnName = null;
        if ($orderColumn == 2) $orderColumnName = 'email';
        if ($orderColumn == 3) $orderColumnName = 'roles';
//        if ($orderColumn == 4) $orderColumnName = 'createAt';

        $items = $this->repository->getBySearch(
            $searchValue,
            ($orderColumnName === null) ? null : ([$orderColumnName, $orderDirection]),
            [$start, $length]
        );

        // Собираем рубрики в массив для передачи в таблицу
        $data = array();
        foreach($items as $item)
        {
            $dataItem = array();
            $dataItem[] = '<span class="datatable-row-id" data-id="' . $item->getId() . '"></span>';
            $dataItem[] = '<a href="' . $this->generateUrl("admin_user_edit", ["id" => $item->getId()]) . '" class="btn btn-sm btn-primary"><i class="far fa-edit fa-fw"></i></a>&nbsp;<a href="' . $this->generateUrl("admin_user_delete", ['id' => $item->getId()]) . '" class="btn btn-sm btn-danger" data-title="Подтвердите действие" data-message="Удалить пользователя?"><i class="far fa-trash-alt fa-fw"></i></a>';

            $dataItem[] = $item->getEmail();
            $dataItem[] = implode("<br>", $item->getRoles());

//            $dataItem[] = $item->getCreateAt()->format("d.m.Y H:i");

            $data[] = $dataItem;
        }

        return $this->json([
            "data" => $data,
            'draw' => $draw,
            'recordsTotal' => $allItemsCount,
            'recordsFiltered' => $itemsCount,
        ]);

    }














    /**
     * @Route("admin/user/create", name="admin_user_create")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return RedirectResponse|Response
     */
    public function create(Request $request, UserPasswordEncoderInterface $passwordEncoder) {


        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $em = $this->getDoctrine()->getManager();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($user->getPlainPassword()) {
                $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
                $user->setPassword($password);
            }
            //$user->setRoles(['ROLE_ADMIN']);

            $em->persist($user);
            $em->flush();
            return $this->redirectToRoute("admin_user");
        }

        $forRender = parent::renderDefault();
        $forRender['title'] = "Форма создания пользователя";
        $forRender['form'] = $form->createView();

        $breadcrumbs = new Breadcrumbs();
        $breadcrumbs->prepend($this->generateUrl("admin_user_create"), 'Создать пользователя');
        $breadcrumbs->prepend($this->generateUrl("admin_user"), 'Пользователи');
        $breadcrumbs->prepend($this->generateUrl("admin_home"), 'Главная');
        $forRender['breadcrumbs'] = $breadcrumbs;

        return $this->render("@FlexySecurity/form.html.twig", $forRender);
    }



    /**
     * @Route("admin/user/edit", name="admin_user_edit")
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function edit(Request $request, UserPasswordEncoderInterface $passwordEncoder) {

        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['id' =>  $request->get('id')]);

        $form = $this->createForm(UserType::class, $user);
        $em = $this->getDoctrine()->getManager();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            if ($user->getPlainPassword()) {
                $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
                $user->setPassword($password);
            }
            //$user->setRoles(['ROLE_ADMIN']);

            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute("admin_user");
        }

        $forRender = parent::renderDefault();
        $forRender['title'] = "Редактирование пользователя";
        $forRender['form'] = $form->createView();

        $breadcrumbs = new Breadcrumbs();
        $breadcrumbs->prepend($this->generateUrl("admin_user_edit"), 'Редактировать пользователя');
        $breadcrumbs->prepend($this->generateUrl("admin_user"), 'Пользователи');
        $breadcrumbs->prepend($this->generateUrl("admin_home"), 'Главная');
        $forRender['breadcrumbs'] = $breadcrumbs;


        return $this->render("@FlexySecurity/form.html.twig", $forRender);
    }

    /**
     * @Route("admin/user/delete", name="admin_user_delete")
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function delete(Request $request) {

        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['id' =>  $request->get('id')]);

        $em = $this->getDoctrine()->getManager();
        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute("admin_user");
    }


}