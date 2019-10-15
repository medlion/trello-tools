<?php

namespace AppBundle\Helper\CardFilter;


use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\ClassLoader\ClassMapGenerator;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class CardFilterFactory
{

    /**
     * @var string[]|ArrayCollection
     */
    private $filterClasses;

    /**
     * CardFilterFactory constructor.
     * @throws \Exception
     */
    public function __construct()
    {

        $availableFilterClasses = $this->getFilesInDirectoryAndSubdirectories();
        $this->validateFilterClasses($availableFilterClasses);


        // Sort classes by their name
        usort($availableFilterClasses, function ($leftClass, $rightClass){
            return $leftClass::getName() <=> $rightClass::getName();
        });
        $this->filterClasses = new ArrayCollection($availableFilterClasses);
    }

    /**
     * Checks that all the configured classes are valid card filters
     *
     * @param array $filterClassNames
     *
     * @throws \Exception
     */
    protected function validateFilterClasses(array $filterClassNames)
    {
        $existingFilterNames = [];
        foreach ($filterClassNames as $class){
            if(!class_implements($class, CardFilterInterface::class)){
                throw new \Exception($class.' does not implement the CardFilterInterface');
            }

            if(in_array($class::getName(), $existingFilterNames)){
                throw new \Exception('More than one filter uses the name \''.$class::geName().'\'');
            }
            $existingFilterNames[] = $class::getName();
        }
    }

    /**
     * Factory function that creates Card Filters using interactive input from the user
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param string          $boardId
     *
     * @return CardFilterInterface
     * @throws \Exception
     */
    public function interactiveMake(InputInterface $input, OutputInterface $output, $boardId)
    {
        // Display option of filters that can be created
        $filterOptions = [];
        foreach($this->getFilterClasses() as $filterClass ){
            $filterOptions[$filterClass::getName()] = $filterClass;
        }
        $filterOptionQuestion = new ChoiceQuestion('Select Filter:', array_merge(array_keys($filterOptions), ['<- Back']));
        $questionHelper = new QuestionHelper();
        $selectedName = $questionHelper->ask($input, $output, $filterOptionQuestion);
        if($selectedName == '<- Back'){
            throw new \Exception('Back option selected');
        }

        // Setup filter
        $output->writeln('Configure Filter: '.$selectedName);
        /** @var CardFilterInterface $filter */
        return $filterOptions[$selectedName]::setUp($input, $output, $boardId);
    }

    /**
     * @return ArrayCollection|string[]
     */
    public function getFilterClasses()
    {
        return $this->filterClasses;
    }

    private function getFilesInDirectoryAndSubdirectories (string $path = __DIR__)
    {
        $arrayOfClasses = [];
        foreach (array_keys(\Composer\Autoload\ClassMapGenerator::createMap($path)) as $class) {
            $reflectionClass = new \ReflectionClass($class);
            if (!$reflectionClass->isAbstract() && $reflectionClass->implementsInterface(CardFilterInterface::class)) {
                $arrayOfClasses[] = $class;
            }
        }

        return $arrayOfClasses;
    }
}
