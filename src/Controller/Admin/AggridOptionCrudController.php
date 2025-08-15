<?php

namespace App\Controller\Admin;

use App\Entity\AggridOption;
use App\Field\JsonField;
use App\Form\Type\JsonFieldType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AggridOptionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AggridOption::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // the labels used to refer to this entity in titles, buttons, etc.
            ->setEntityLabelInSingular('AgGrid Option')
            ->setEntityLabelInPlural('AgGrid Options');
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('gridName'),
            TextField::new('field'),
            TextField::new('headerName', 'Nom affiché'),
            TextField::new('type', 'Type'),
            IntegerField::new('minWidth', 'Largeur min.')->hideOnIndex(),
            BooleanField::new('sortable', 'Triable')->hideOnIndex(),
            TextField::new('filter', 'Type de filtre')->hideOnIndex(),
            JsonField::new('cellStyle', 'Cell Style (JSON)')
                ->onlyOnForms()
                ->setFormType(JsonFieldType::class),
            IntegerField::new('flex', 'Flex')->hideOnIndex(),
            TextField::new('aggFunc', 'Totaux')->hideOnIndex(),
            BooleanField::new('visible', 'Visible')->hideOnIndex(),
            IntegerField::new('orderIndex', 'Ordre')->hideOnIndex(),

        ];
    }

}
