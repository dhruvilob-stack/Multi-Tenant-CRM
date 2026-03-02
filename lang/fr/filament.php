<?php

return [
    'admin' => [
        'groups' => [
            'configuration' => 'Configuration',
            'my_network' => 'Mon Réseau',
            'settings' => 'Paramètres',
            'reports' => 'Rapports',
            'operations' => 'Opérations',
        ],
        'pages' => [
            'profile' => [
                'nav' => 'Mon Profil',
            ],
            'invitations' => [
                'expires' => 'expire',
                'empty' => 'Aucune invitation envoyée pour le moment.',
            ],
            'distributor_invitations' => [
                'nav' => 'Invitations Envoyées',
                'heading' => 'Invitations Distributeurs',
            ],
            'manufacturer_invitations' => [
                'nav' => 'Invitations Envoyées',
                'heading' => 'Invitations Fabricants',
            ],
            'vendor_invitations' => [
                'nav' => 'Invitations Envoyées',
                'heading' => 'Invitations Vendeurs',
            ],
            'email_templates' => [
                'nav' => 'Modèles d’Email',
                'heading' => 'Modèles d’Email',
                'description' => 'Personnalisez les contenus d’email d’invitation, de devis et de facture.',
            ],
            'reports_sales' => [
                'nav' => 'Rapport des Ventes',
                'heading' => 'Rapport des Ventes',
                'description' => 'Consultez la conversion des commandes et les totaux de factures par rôle et période.',
            ],
            'reports_inventory' => [
                'nav' => 'Rapport d’Inventaire',
                'heading' => 'Rapport d’Inventaire',
                'description' => 'Analysez le stock, les réserves et les points de tension de rupture.',
            ],
            'reports_commissions' => [
                'nav' => 'Rapport des Commissions',
                'heading' => 'Rapport des Commissions',
                'description' => 'Analysez les commissions cumulées par étape de chaîne et statut de paiement.',
            ],
            'stock_movements' => [
                'nav' => 'Mouvements de Stock',
                'heading' => 'Mouvements de Stock',
                'description' => 'Suivez les transferts et ajustements entre niveaux hiérarchiques.',
            ],
            'settings' => [
                'nav' => 'Paramètres Système',
                'heading' => 'Paramètres Système',
                'description' => 'Valeurs par défaut de l’organisation pour taxes, devise et facturation.',
            ],
        ],
    ],
    'super_admin' => [
        'groups' => [
            'system' => 'Système',
            'tenant_management' => 'Gestion des Locataires',
            'analytics' => 'Analytique',
            'user_management' => 'Gestion des Utilisateurs',
        ],
        'pages' => [
            'audit_logs' => [
                'nav' => 'Journaux d’Audit',
                'heading' => 'Journaux d’Audit',
                'description' => 'La piste d’audit globale apparaîtra ici quand la journalisation sera activée.',
            ],
            'email_templates' => [
                'nav' => 'Modèles d’Email',
                'heading' => 'Modèles d’Email',
                'description' => 'Gérez les modèles d’invitation, de devis et de facture.',
            ],
            'plans' => [
                'nav' => 'Plans d’Abonnement',
                'starter' => [
                    'heading' => 'Starter',
                    'description' => 'Pour les nouvelles organisations',
                    'price' => '$49 / mois',
                ],
                'growth' => [
                    'heading' => 'Croissance',
                    'description' => 'Pour les réseaux en expansion',
                    'price' => '$149 / mois',
                ],
                'enterprise' => [
                    'heading' => 'Entreprise',
                    'description' => 'Pour les grandes chaînes',
                    'price' => '$499 / mois',
                ],
            ],
            'reports_revenue' => [
                'nav' => 'Rapports de Revenus',
                'heading' => 'Rapports de Revenus',
                'description' => 'Suivez les totaux de factures, soldes payés et la répartition des revenus par locataire.',
            ],
            'reports_usage' => [
                'nav' => 'Statistiques d’Utilisation',
                'heading' => 'Statistiques d’Utilisation',
                'description' => 'Surveillez locataires actifs, utilisateurs par rôle, conversion des devis et flux des commandes.',
            ],
            'roles' => [
                'nav' => 'Permissions des Rôles',
                'heading' => 'Permissions des Rôles',
                'description' => 'La matrice des permissions est appliquée par ressources et actions de workflow.',
            ],
            'settings' => [
                'nav' => 'Paramètres Globaux',
                'heading' => 'Paramètres Globaux',
                'description' => 'Configurez les valeurs par défaut pour taxes, devise, expiration des invitations et branding.',
            ],
        ],
    ],
];
