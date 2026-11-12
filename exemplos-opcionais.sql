-- Opcional: importe este arquivo DEPOIS de database.sql para visualizar fichas de exemplo.
-- TODOS OS REGISTROS ABAIXO SÃO ILUSTRATIVOS E NÃO REPRESENTAM AVES REAIS NEM DISPONÍVEIS PARA COMERCIALIZAÇÃO.
USE cbm_criadouro;
INSERT INTO birds (name,scientific_name,sex,age_months,description,origin_info,feeding,photo_path,is_visible,is_featured,is_available)
SELECT 'Coleiro • exemplo', 'Sporophila caerulescens','Macho',8,'Ficha demonstrativa: descrição ilustrativa. Substitua todas as informações pelos dados corretos do seu plantel.','Cadastro de demonstração','Alimentação: informe o manejo real.','assets/images/coleiro.jpg',1,1,1
WHERE NOT EXISTS (SELECT 1 FROM birds WHERE name='Coleiro • exemplo');
INSERT INTO birds (name,scientific_name,sex,age_months,description,origin_info,photo_path,is_visible,is_featured,is_available)
SELECT 'Curió • exemplo','Sporophila angolensis','Macho',12,'Ficha demonstrativa para apresentar o catálogo CBM.','Cadastro de demonstração','assets/images/curio.jpg',1,1,1
WHERE NOT EXISTS (SELECT 1 FROM birds WHERE name='Curió • exemplo');
INSERT INTO birds (name,scientific_name,sex,age_months,description,origin_info,photo_path,is_visible,is_featured,is_available)
SELECT 'Trinca-ferro • exemplo','Saltator similis','Indefinido',10,'Ficha demonstrativa. Informações e imagem usadas apenas para exibição do layout.','Cadastro de demonstração','assets/images/trinca-ferro.jpg',1,0,1
WHERE NOT EXISTS (SELECT 1 FROM birds WHERE name='Trinca-ferro • exemplo');
INSERT INTO birds (name,scientific_name,sex,age_months,description,origin_info,photo_path,is_visible,is_featured,is_available)
SELECT 'Canário-da-terra • exemplo','Sicalis flaveola','Fêmea',7,'Ficha fictícia de demonstração para o site.','Cadastro de demonstração','assets/images/canario.jpg',1,0,1
WHERE NOT EXISTS (SELECT 1 FROM birds WHERE name='Canário-da-terra • exemplo');
