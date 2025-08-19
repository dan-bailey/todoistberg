/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { 
    useBlockProps, 
    InspectorControls,
    RichText
} from '@wordpress/block-editor';
import { 
    Placeholder, 
    Notice,
    Card,
    CardBody,
    CardHeader,
    Spinner,
    PanelBody,
    ToggleControl,
    ColorPicker,
    RangeControl,
    SelectControl
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

/**
 * Todo Stats Block Edit Component
 */
export default function TodoStatsBlock({ attributes, setAttributes }) {
    console.log('🔍 TodoStatsBlock attributes:', attributes);
    const { showToday, showWeek, showMonth, showPastDue, numberColor, borderWidth, borderColor, borderRadius, backgroundColor, margin, padding, headlineAlignment, title } = attributes;
    const [stats, setStats] = useState({});
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    
    const blockProps = useBlockProps({
        className: 'todoistberg-todo-stats',
        style: {
            border: borderWidth ? `${borderWidth}px solid ${borderColor}` : 'none',
            borderRadius: `${borderRadius}px`,
            backgroundColor,
            marginBottom: `${margin}px`,
            padding: `${padding}px`
        }
    });

    // Fetch stats when attributes change
    useEffect(() => {
        if (window.todoistbergData?.hasToken) {
            fetchStats();
        }
    }, [showToday, showWeek, showMonth, showPastDue]);

    const fetchStats = async () => {
        console.log('🔄 Todoistberg: Starting stats fetch...');
        console.log('📊 Todoistberg: Show settings:', { showToday, showWeek, showMonth, showPastDue });
        
        setLoading(true);
        setError('');

        try {
            const formData = new FormData();
            formData.append('action', 'todoistberg_get_stats');
            formData.append('show_today', showToday);
            formData.append('show_week', showWeek);
            formData.append('show_month', showMonth);
            formData.append('show_past_due', showPastDue);
            formData.append('nonce', window.todoistbergData?.nonce || '');

            const ajaxUrl = window.todoistbergData?.ajaxUrl || window.todoistbergFrontend?.ajaxUrl || window.ajaxurl || '/wp-admin/admin-ajax.php';
            console.log('📤 Todoistberg: Sending AJAX request to:', ajaxUrl);
            console.log('🔑 Todoistberg: Has token:', !!window.todoistbergData?.hasToken);

            const response = await fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            });

            console.log('📥 Todoistberg: Response status:', response.status);
            console.log('📥 Todoistberg: Response ok:', response.ok);

            if (!response.ok) {
                throw new Error('Failed to fetch statistics');
            }

            const data = await response.json();
            console.log('📊 Todoistberg: Response data:', data);
            
            if (data.success) {
                console.log('✅ Todoistberg: Stats received:', data.data);
                setStats(data.data);
            } else {
                console.error('❌ Todoistberg: API error:', data.data);
                throw new Error(data.data || 'Failed to fetch statistics');
            }
        } catch (err) {
            console.error('💥 Todoistberg: Fetch error:', err);
            setError(err.message);
            setStats({});
        } finally {
            setLoading(false);
            console.log('🏁 Todoistberg: Stats fetch completed');
        }
    };

    // Debug logging
    console.log('🔧 Rendering InspectorControls panel');
    console.log('🔧 showPastDue value:', showPastDue);

    const renderStats = () => {
        if (loading) {
            return (
                <div style={{ textAlign: 'center', padding: '20px' }}>
                    <Spinner />
                    <p>{__('Loading statistics...', 'todoistberg')}</p>
                </div>
            );
        }

        if (error) {
            return (
                <Notice status="error" isDismissible={false}>
                    {error}
                </Notice>
            );
        }

        return (
            <div className="todoistberg-stats-grid">
                {showToday && (
                    <div className="todoistberg-stat-item">
                        <span className="todoistberg-stat-number" style={{ color: numberColor }}>
                            {stats.today || 0}
                        </span>
                        <span className="todoistberg-stat-label">
                            {__('Completed Today', 'todoistberg')}
                        </span>
                    </div>
                )}
                
                {showWeek && (
                    <div className="todoistberg-stat-item">
                        <span className="todoistberg-stat-number" style={{ color: numberColor }}>
                            {stats.week || 0}
                        </span>
                        <span className="todoistberg-stat-label">
                            {__('Completed This Week', 'todoistberg')}
                        </span>
                    </div>
                )}
                
                {showMonth && (
                    <div className="todoistberg-stat-item">
                        <span className="todoistberg-stat-number" style={{ color: numberColor }}>
                            {stats.month || 0}
                        </span>
                        <span className="todoistberg-stat-label">
                            {__('Completed This Month', 'todoistberg')}
                        </span>
                    </div>
                )}
                
                {showPastDue && (
                    <div className="todoistberg-stat-item todoistberg-stat-past-due">
                        <span className="todoistberg-stat-number">
                            {stats.pastDue || 0}
                        </span>
                        <span className="todoistberg-stat-label">
                            {__('Past Due Tasks', 'todoistberg')}
                        </span>
                    </div>
                )}
            </div>
        );
    };

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Statistics Settings', 'todoistberg')} initialOpen={true}>
                    <ToggleControl
                        label={__('Show Today', 'todoistberg')}
                        checked={showToday}
                        onChange={(value) => setAttributes({ showToday: value })}
                    />
                    
                    <ToggleControl
                        label={__('Show This Week', 'todoistberg')}
                        checked={showWeek}
                        onChange={(value) => setAttributes({ showWeek: value })}
                    />
                    
                    <ToggleControl
                        label={__('Show This Month', 'todoistberg')}
                        checked={showMonth}
                        onChange={(value) => setAttributes({ showMonth: value })}
                    />
                    
                    <ToggleControl
                        label={__('Show Past Due Tasks', 'todoistberg')}
                        checked={showPastDue}
                        onChange={(value) => setAttributes({ showPastDue: value })}
                    />
                    
                    <div style={{ marginTop: '16px' }}>
                        <label style={{ display: 'block', marginBottom: '8px', fontSize: '11px', fontWeight: '500', textTransform: 'uppercase' }}>
                            {__('Number Color', 'todoistberg')}
                        </label>
                        <ColorPicker
                            color={numberColor}
                            onChange={(value) => setAttributes({ numberColor: value })}
                            enableAlpha
                        />
                    </div>
                </PanelBody>
                
                <PanelBody title={__('Block Styling', 'todoistberg')} initialOpen={false}>
                    <RangeControl
                        label={__('Border Width', 'todoistberg')}
                        value={borderWidth}
                        onChange={(value) => setAttributes({ borderWidth: value })}
                        min={0}
                        max={10}
                    />
                    
                    <div style={{ marginTop: '16px' }}>
                        <label style={{ display: 'block', marginBottom: '8px', fontSize: '11px', fontWeight: '500', textTransform: 'uppercase' }}>
                            {__('Border Color', 'todoistberg')}
                        </label>
                        <ColorPicker
                            color={borderColor}
                            onChange={(value) => setAttributes({ borderColor: value })}
                            enableAlpha
                        />
                    </div>
                    
                    <RangeControl
                        label={__('Corner Radius', 'todoistberg')}
                        value={borderRadius}
                        onChange={(value) => setAttributes({ borderRadius: value })}
                        min={0}
                        max={50}
                    />
                    
                    <div style={{ marginTop: '16px' }}>
                        <label style={{ display: 'block', marginBottom: '8px', fontSize: '11px', fontWeight: '500', textTransform: 'uppercase' }}>
                            {__('Background Color', 'todoistberg')}
                        </label>
                        <ColorPicker
                            color={backgroundColor}
                            onChange={(value) => setAttributes({ backgroundColor: value })}
                            enableAlpha
                        />
                    </div>
                    
                    <RangeControl
                        label={__('Margin (px)', 'todoistberg')}
                        value={margin}
                        onChange={(value) => setAttributes({ margin: value })}
                        min={0}
                        max={100}
                    />
                    
                    <RangeControl
                        label={__('Padding (px)', 'todoistberg')}
                        value={padding}
                        onChange={(value) => setAttributes({ padding: value })}
                        min={0}
                        max={100}
                    />
                </PanelBody>
                
                <PanelBody title={__('Headline Settings', 'todoistberg')} initialOpen={false}>
                    <SelectControl
                        label={__('Headline Alignment', 'todoistberg')}
                        value={headlineAlignment}
                        options={[
                            { label: __('Left', 'todoistberg'), value: 'left' },
                            { label: __('Center', 'todoistberg'), value: 'center' },
                            { label: __('Right', 'todoistberg'), value: 'right' }
                        ]}
                        onChange={(value) => setAttributes({ headlineAlignment: value })}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                <RichText
                    tagName="h3"
                    className="todoistberg-stats-title"
                    style={{ textAlign: headlineAlignment }}
                    value={title}
                    onChange={(value) => setAttributes({ title: value })}
                    placeholder={__('Enter title...', 'todoistberg')}
                />
                
                {!window.todoistbergData?.hasToken ? (
                    <Notice status="warning" isDismissible={false}>
                        {__('Please configure your Todoist API token in the plugin settings.', 'todoistberg')}
                    </Notice>
                ) : (
                    <Card>
                        <CardHeader>
                            <h4>{__('Statistics Preview', 'todoistberg')}</h4>
                        </CardHeader>
                        <CardBody>
                            {renderStats()}
                        </CardBody>
                    </Card>
                )}
            </div>
        </>
    );
}
