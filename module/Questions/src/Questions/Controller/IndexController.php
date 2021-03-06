<?php

namespace Questions\Controller;

use Questions\Entity\Email;
use Questions\Entity\Question;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Questions\Service;
use DoctrineModule\Paginator\Adapter\Collection as CollectionAdapter;
use Zend\Paginator\Paginator;

class IndexController extends AbstractActionController
{
    protected $questionsService;
    protected $questionsForm;

    public function indexAction()
    {
        $questions = $this->getQuestionsService();

        // Create the adapter
        $adapter = new CollectionAdapter($questions->getQuestions());

        // Create the paginator itself
        $paginator = new Paginator($adapter);
        $paginator->setCurrentPageNumber($this->params()->fromRoute('page'))
            ->setItemCountPerPage(50);

        return new ViewModel(array(
            'paginator' => $paginator
        ));
    }
    public function addAction()
    {
        $form = $this->getQuestionForm();
        $form->get('question')->remove('id');

        $request = $this->getRequest();
        if ($request->isPost()) {
            $question = new Question();
            $form->bind($question);
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $questions = $this->getQuestionsService();
                $question = $questions->create($question);

                // Redirect to list of questions
                return $this->redirect()->toRoute('questions/import-emails', array('question' => $question->getId()));
            }
        }

        return array('form' => $form);
    }

    public function editAction()
    {
        $id = (int)$this->params('id');

        $question_service = $this->getQuestionsService();
        $question = $question_service->getQuestion($id);

        if (!$question) {
            return $this->redirect()->toRoute('questions');
        }

        $form = $this->getQuestionForm();
        $form->bind($question);
        $form->get('submit')->setAttribute('value', 'Edit');

        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost());
            if ($form->isValid()) {
                $question_service->update($form->getData());

                // Redirect to list of albums
                return $this->redirect()->toRoute('questions');
            }
        }

        return array(
            'id' => $id,
            'form' => $form,
        );
    }

    public function questionAction()
    {
        $id = (int)$this->params('id');

        $question_service = $this->getQuestionsService();
        $question = $question_service->getQuestion($id);

        if (!$question) {
            return $this->redirect()->toRoute('questions');
        }

        return array(
            'question' => $question,
        );
    }

    public function showAction()
    {
        $id = (int)$this->params('email');

        $question_service = $this->getQuestionsService();
        $email = $question_service->getEmail($id, false);

        if (!$email) {
            die('no email');
        }

        $question_service->updateEmailStatus($email, Email::STATE_VIEWED);

        $response = $this->getResponse();
        $response->setContent(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAMAAAAoyzS7AAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAA1JREFUeNoBAgD9/wAAAAIAAVMrnDAAAAAASUVORK5CYII='));
        $response
            ->getHeaders()
            ->addHeaderLine('Content-Transfer-Encoding', 'binary')
            ->addHeaderLine('Content-Type', 'image/png')
            ->addHeaderLine('Content-Length', mb_strlen($response->getContent()));

        return $response;
    }

    public function sendAction()
    {
        $id = (int)$this->params('question');
        $question_service = $this->getQuestionsService();
        $question = $question_service->getQuestion($id);

        if (!$question) {
            return $this->redirect()->toRoute('questions');
        }

        $question_service->sendQuestion($question);

        // Redirect to list of questions
        return $this->redirect()->toRoute('questions');
    }

    public function voteAction()
    {
        $email_id = (int)$this->params('email');
        $answer_id = (int)$this->params('answer');

        $question_service = $this->getQuestionsService();

        $email = $question_service->vote($email_id, $answer_id);

        // Turn off the layout, i.e. only render the view script.
        $viewModel = new ViewModel(array(
            'email' => $email
        ));
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    public function deleteAction()
    {
        $id = (int)$this->params('id');
        $question_service = $this->getQuestionsService();
        $question = $question_service->getQuestion($id);

        if (!$question) {
            return $this->redirect()->toRoute('questions');
        }

        $question_service->removeQuestion($question);

        // Redirect to list of questions
        return $this->redirect()->toRoute('questions');
    }

    public function setQuestionsService($questionsService)
    {
        $this->questionsService = $questionsService;
        return $this;
    }

    public function getQuestionsService()
    {
        return $this->questionsService;
    }

    public function setQuestionForm($questionsForm)
    {
        $this->questionsForm = $questionsForm;
        return $this;
    }

    public function getQuestionForm()
    {
        return $this->questionsForm;
    }
}
