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

            /**
             * Returns true if the model is a product model
             * @return {Boolean}
             */
            isProductModel() {
                return this.model.get('document_type') === 'product_model';
            },

            /**
             * Get the name of the completeness cell based on product type
             * @return {String}
             */
            getCompletenessCellType() {
                return this.isProductModel() ? 'complete_variant_products' : 'completeness';
            },

            /**
             * If the row contains a checked checkbox, set the selected class
             * @param {HTMLElement} row
             */
            setCheckedClass(row) {
                const isChecked = $('input[type="checkbox"]:checked', row).length;
                row.toggleClass('AknGrid-bodyRow--selected', 1 === isChecked);
            },

            /**
             * Returns the 'thumbnail' size image path for a product OR the dummy image
             *
             * @return {String}
             */
            getThumbnailImagePath() {
                const image = this.model.get('image');

                if (null === image) return '/media/show/undefined/preview';

                return MediaUrlGenerator.getMediaShowUrl(image.filePath, 'thumbnail');
            },

            /**
             * Return the cells that match the given column names
             * @param  {Array} columnNames
             * @return {Array}
             */
            getCells(columnNames) {
                return this.cells.filter(cell => {
                    return columnNames.includes(cell.column.get('name'));
                });
            },

            /**
             * Renders the completeness, mass actions and checkbox (the '') cells
             * @param  {HTMLElement} row
             */
            renderCells(row) {
                const type = this.getCompletenessCellType();
                const cells = this.getCells([type, 'massAction', '']);
                cells.forEach(cell => row.append(cell.render().el));
            },

            /**
             * {@inheritdoc}
             */
            render() {
                const productLabel = this.model.get('label');
                const isProductModel = this.isProductModel();
                const row = $(this.rowTemplate({ isProductModel, productLabel }));

                const thumbnail = this.thumbnailTemplate({
                    isProductModel,
                    identifier: this.model.get('identifier'),
                    imagePath: this.getThumbnailImagePath(),
                    label: this.model.get('label')
                });

                row.empty().append(thumbnail);
                this.renderCells(row);
                this.$el.empty().html(row);

                row.on('click', this.onClick.bind(this));
                row.on('change', 'input[type="checkbox"]', this.setCheckedClass.bind(this, row));

                return this.delegateEvents();
            }
        });
    });
