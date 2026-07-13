import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	SelectControl,
	Button,
	Icon,
	__experimentalVStack as VStack
} from '@wordpress/components';
import { plus, close, dragHandle } from '@wordpress/icons';
import { useState, useEffect } from '@wordpress/element';
import {__, _x} from "@wordpress/i18n";

export default function Edit({ attributes, setAttributes }) {
	const blockProps = useBlockProps();
	const [draggedIndex, setDraggedIndex] = useState(null);

	const availableOptions = window.wpcSortingData?.sortingOptions || [
		{ label: __('Default sorting', 'filter-everything'), value: 'default' },
		{ label: __('By title: alphabetical', 'filter-everything'), value: 'title_asc' },
		{ label: __('By title: reverse', 'filter-everything'), value: 'title_desc' },
		{ label: __('By date: oldest first', 'filter-everything'), value: 'date_asc' },
		{ label: __('By date: newest first', 'filter-everything'), value: 'date_desc' }
	];


	useEffect(() => {
		if (!attributes.sorting_options || attributes.sorting_options.length === 0) {
			const sortingDefaultOptions = window.wpcSortingData?.sortingDefaultOptions || false;
			if(sortingDefaultOptions){
				const addDefaultSortingOptions = sortingDefaultOptions.slice(0, 5).map((option, index) => ({
					id: Date.now() + index,
					title: option.title,
					order_by: option.order_by,
					order: option.order
				}));
				setAttributes({ sorting_options: addDefaultSortingOptions });
			}
		}
	}, []);

	const addSortingOption = () => {
		console.log(attributes.sorting_options);
		let optionsSum = attributes.sorting_options.length + 1;
		const newOptions = [
			...(attributes.sorting_options || []),
			{
				id: Date.now(),
				title:  __('Item #', 'filter-everything') + optionsSum,
				order_by: 'default',
				order: 'asc'
			}
		];
		setAttributes({ sorting_options: newOptions });
	};

	const removeSortingOption = (id) => {
		const newOptions = (attributes.sorting_options || []).filter(
			option => option.id !== id
		);
		setAttributes({ sorting_options: newOptions });
	};

	const updateSortingOption = (id, field, value) => {
		const newOptions = (attributes.sorting_options || []).map(option => {
			if (option.id === id) {
				return { ...option, [field]: value };
			}
			return option;
		});
		setAttributes({ sorting_options: newOptions });
	};

	const handleDragStart = (index) => {
		setDraggedIndex(index);
	};

	const handleDragOver = (e, index) => {
		e.preventDefault();
		if (draggedIndex === null || draggedIndex === index) return;

		const items = [...(attributes.sorting_options || [])];
		const draggedItem = items[draggedIndex];
		items.splice(draggedIndex, 1);
		items.splice(index, 0, draggedItem);

		setAttributes({ sorting_options: items });
		setDraggedIndex(index);
	};

	const handleDragEnd = () => {
		setDraggedIndex(null);
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'filter-everything' ) } initialOpen={true}>
					<TextControl
						__next40pxDefaultSize
						label={ __('Title', 'filter-everything') }
						value={ attributes.title || '' }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
					/>
					<VStack spacing={3}>
						{(attributes.sorting_options || []).map((option, index) => (
							<div
								key={option.id}
								draggable
								onDragStart={() => handleDragStart(index)}
								onDragOver={(e) => handleDragOver(e, index)}
								onDragEnd={handleDragEnd}
								style={{
									padding: '12px',
									border: '1px solid #ddd',
									borderRadius: '4px',
									background: draggedIndex === index ? '#e0e0e0' : '#f9f9f9',
									cursor: 'move'
								}}
							>
								<div style={{
									display: 'flex',
									alignItems: 'center',
									justifyContent: 'space-between',
									marginBottom: '8px'
								}}>
									<div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
										<Icon icon={dragHandle} size={16} />
										<strong>
											{ __('Item #', 'filter-everything') }{index + 1}
										</strong>
									</div>
									<Button
										icon={close}
										label={ __('Remove', 'filter-everything') }
										onClick={() => removeSortingOption(option.id)}
										isDestructive
										size="small"
									/>
								</div>

								<TextControl
									label={ __('Title', 'filter-everything') }
									value={option.title}
									onChange={(value) => updateSortingOption(option.id, 'title', value)}
									placeholder="e.g., Default sorting"
								/>

								<SelectControl
									label={ __('Order By', 'filter-everything') }
									value={option.order_by}
									options={availableOptions}
									onChange={(value) => updateSortingOption(option.id, 'order_by', value)}
								/>

								{(option.order_by === 'm' || option.order_by === 'n') && (
									<TextControl
										label={ __('Meta Key', 'filter-everything') }
										value={option.meta_key || ''}
										onChange={(value) => updateSortingOption(option.id, 'meta_key', value)}
									/>
								)}

								<SelectControl
									label={ __('Order', 'filter-everything') }
									value={option.order}
									options={[
										{
											label: _x('ASC', 'sorting', 'filter-everything'),
											value: 'asc'
										},
										{
											label: _x('DESC', 'sorting', 'filter-everything'),
											value: 'desc'
										}
									]}
									onChange={(value) => updateSortingOption(option.id, 'order', value)}
								/>
							</div>
						))}
					</VStack>

					<Button
						variant="primary"
						onClick={addSortingOption}
						icon={plus}
						style={{ width: '100%', marginTop: '12px' }}
					>
						{ __( 'Add sorting option', 'filter-everything' ) }
					</Button>

				</PanelBody>
			</InspectorControls>

			<p { ...useBlockProps() }>
				{ __( 'Filter Everything - Sorting', 'filter-everything' ) }
			</p>
		</>
	);
}
