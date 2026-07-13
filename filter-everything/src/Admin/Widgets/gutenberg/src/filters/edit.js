/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';

import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */

export default function Edit({ attributes, setAttributes } ) {
	const { title, show_count, chips  } = attributes;
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'filter-everything' ) }>
					<TextControl
						__next40pxDefaultSize
						label={ __(
							'Title',
							'filter-everything'
						) }
						value={ title || '' }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
					/>
					<ToggleControl
						checked={ !! show_count }
						label={ __(
							'Show the number of posts found',
							'filter-everything'
						) }
						onChange={ () =>
							setAttributes( {
								show_count: ! show_count,
							} )
						}
					/>
					<ToggleControl
						checked={ !! chips }
						label={ __(
							'Show selected terms (Chips)',
							'filter-everything'
						) }
						onChange={ () =>
							setAttributes( {
								chips: ! chips,
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<p { ...useBlockProps() }>
				{ __( 'Filter Everything - Filters', 'filter-everything' ) }
			</p>
		</>
	);
}
