define(
    [
        'jquery',
        'underscore',
        'backgrid',
        'oro/datagrid/row',
        'pim/template/datagrid/row/product',
        'pim/template/datagrid/row/product-thumbnail',
        'pim/media-url-generator'
    ],
    function(
        $,
        _,
        Backgrid,
        BaseRow,
        rowTemplate,
        thumbnailTemplate,
        MediaUrlGenerator
    ) {
        return BaseRow.extend({
            tagName: 'div',
            rowTemplate: _.template(rowTemplate),
            thumbnailTemplate: _.template(thumbnailTemplate),
            setCheckedClass(row) {
                const isChecked = $('.AknGrid-bodyCell--checkbox input:checked', row).length;
                row.toggleClass('AknGrid-bodyRow--selected', 1 === isChecked);
            },
            getCells(columnNames) {
                return this.cells.filter(cell => {
                    return columnNames.includes(cell.column.get('name'));
                });
            },
            getThumbnailImagePath() {
                const image = this.model.get('image');

                if (null === image) return '/media/show/undefined/preview';

                return MediaUrlGenerator.getMediaShowUrl(image.filePath, 'thumbnail');
            },
            render() {
                const productLabel = this.model.get('label');
                const isProductModel = this.model.get('document_type') === 'product_model';
                const row = $(this.rowTemplate({ isProductModel, productLabel }));

                const thumbnail = this.thumbnailTemplate({
                    isProductModel,
                    identifier: this.model.get('identifier'),
                    imagePath: this.getThumbnailImagePath(),
                    label: this.model.get('label'),
                    completeness: this.model.get('completeness'),
                    complete_variant_products: this.model.get('complete_variant_products')
                });

                row.empty().append(thumbnail);

                const completeType = isProductModel ? 'complete_variant_products' : 'completeness';
                const cells = this.getCells([completeType, 'massAction', '']);
                cells.forEach(cell => row.append(cell.render().el));

                this.$el.empty().html(row);

                row.on('click', this.onClick.bind(this));
                row.on('change', 'input[type="checkbox"]', this.setCheckedClass.bind(this, row));

                return this.delegateEvents();
            }
        });
    });
