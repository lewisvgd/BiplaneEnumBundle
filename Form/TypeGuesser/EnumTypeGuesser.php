<?php
namespace Biplane\EnumBundle\Form\TypeGuesser;

use Biplane\EnumBundle\Form\Type\EnumType;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;

class EnumTypeGuesser implements FormTypeGuesserInterface
{
    const ANN_NAME = 'Biplane\EnumBundle\Configuration\Enum';

    /**
     * Annotation reader
     * @var Doctrine\Common\Annotations\Reader
     */
    private $annReader;

    public function __construct($annReader)
    {
        $this->annReader = $annReader;
    }

    public function guessType($class, $property)
    {
        $reflectionClass = new \ReflectionClass($class);

        if (!$reflectionClass->hasProperty($property)) {

            return null;
        }

        $propertyObj = $reflectionClass->getProperty($property);

        if (($ann = $this->annReader->getPropertyAnnotation($propertyObj, self::ANN_NAME))) {

            $enumClass = $ann->value;

            return new TypeGuess(EnumType::class, array('enum_class' => $enumClass), Guess::HIGH_CONFIDENCE);
        }

        return null;

    }

    public function guessRequired($class, $property)
    {
        return null;
    }

    public function guessMaxLength($class, $property)
    {
        return null;
    }

    public function guessPattern($class, $property)
    {
        return null;
    }

}